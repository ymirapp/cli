<?php

declare(strict_types=1);

/*
 * This file is part of Ymir command-line tool.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Cli\Tests\Integration\Command\Provider;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Provider\UpdateProviderCommand;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Requirement\CloudProviderAuthenticationMethodRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;
use Ymir\Sdk\Exception\ClientException;

class UpdateProviderCommandTest extends TestCase
{
    public static function provideAuthenticationUpdates(): array
    {
        return [
            'pending defaults to IAM role' => ['pending', null, '', CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE],
            'pending explicitly uses access key' => ['pending', null, '1', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
            'rotates access key by default' => ['connected', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, '', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
            'repairs disconnected IAM role by default' => ['disconnected', CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE, '', CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE],
            'switches access key to IAM role' => ['connected', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, '0', CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE],
            'switches IAM role to access key' => ['connected', CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE, '1', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
        ];
    }

    public static function provideProviderStatuses(): array
    {
        return [['pending'], ['connected'], ['disconnected']];
    }

    public function testFailedAuthenticationUpdatePreservesProvider(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
        ]);
        $detailedProvider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => [
                'method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);
        $credentials = ['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'];
        $clientException = new ClientException(new GuzzleClientException('Invalid update', new Request('PATCH', '/providers/123'), new Response(422)));

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($detailedProvider, $credentials, null)->andThrow($clientException)->ordered();
        $this->apiClient->shouldNotReceive('createProvider');
        $this->apiClient->shouldNotReceive('deleteProvider');

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(UpdateProviderCommand::NAME));
        $tester->setInputs(['', 'yes', '0', $credentials['role_arn']]);

        try {
            $tester->execute(['provider' => '123']);

            $this->fail('The failed authentication update should stop the command');
        } catch (ClientException $exception) {
            $this->assertSame($clientException, $exception);
        }

        $this->assertStringNotContainsString('Cloud provider updated', $tester->getDisplay());
    }

    /**
     * @dataProvider provideAuthenticationUpdates
     */
    public function testUpdateProviderAuthentication(string $status, ?string $currentMethod, string $methodInput, string $selectedMethod): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'status' => $status,
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => $currentMethod],
        ]);
        $detailedProvider = CloudProviderFactory::create([
            'status' => $status,
            'id' => 123,
            'name' => 'AWS',
            'authentication' => [
                'method' => $currentMethod,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);
        $inputs = ['', 'yes', $methodInput];
        $credentials = ['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'];

        if (CloudProviderAuthenticationMethodRequirement::ACCESS_KEY === $selectedMethod) {
            $inputs = array_merge($inputs, ['new-key', 'new-secret']);
            $credentials = ['key' => 'new-key', 'secret' => 'new-secret'];
        } else {
            $inputs[] = $credentials['role_arn'];
        }

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($detailedProvider, $credentials, null)->ordered();

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], $inputs);

        if (CloudProviderAuthenticationMethodRequirement::ACCESS_KEY === $selectedMethod) {
            $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        } else {
            $this->assertStringContainsString('Ymir AWS account ID: 111122223333', $tester->getDisplay());
            $this->assertStringContainsString('External ID: server-external-id', $tester->getDisplay());
            $this->assertStringContainsString('Role name: server-role-name', $tester->getDisplay());
        }

        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }

    public function testUpdateProviderAuthenticationNonInteractivelyWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE],
        ]);
        $detailedProvider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE],
        ]);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($detailedProvider, ['key' => 'profile-key', 'secret' => 'profile-secret'], null)->ordered();

        $awsDir = $this->homeDir.'/.aws';
        mkdir($awsDir);
        file_put_contents($awsDir.'/credentials', "[work]\naws_access_key_id=profile-key\naws_secret_access_key=profile-secret\n");

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(UpdateProviderCommand::NAME));
        $tester->execute(['provider' => '123', '--aws-profile' => 'work'], ['interactive' => false]);

        $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
        $this->assertStringNotContainsString('Would you like to update the authentication method or credentials?', $tester->getDisplay());
        $this->assertStringNotContainsString('Which authentication method would you like to use?', $tester->getDisplay());
    }

    public function testUpdateProviderAuthenticationNonInteractivelyWithRoleArn(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
        ]);
        $detailedProvider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => [
                'method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);
        $roleArn = 'arn:aws:iam::444455556666:role/customer-role';

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($detailedProvider, ['role_arn' => $roleArn], null)->ordered();

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(UpdateProviderCommand::NAME));
        $tester->execute(['provider' => '123', '--role-arn' => $roleArn], ['interactive' => false]);

        $this->assertStringContainsString('Ymir AWS account ID: 111122223333', $tester->getDisplay());
        $this->assertStringContainsString('External ID: server-external-id', $tester->getDisplay());
        $this->assertStringContainsString('Role name: server-role-name', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
        $this->assertStringNotContainsString('Would you like to update the authentication method or credentials?', $tester->getDisplay());
        $this->assertStringNotContainsString('Which authentication method would you like to use?', $tester->getDisplay());
    }

    public function testUpdateProviderNameAndAuthenticationWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => ['method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY],
        ]);
        $detailedProvider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
            'authentication' => [
                'method' => CloudProviderAuthenticationMethodRequirement::ACCESS_KEY,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($detailedProvider, ['key' => 'profile-key', 'secret' => 'profile-secret'], 'Updated AWS')->ordered();

        $awsDir = $this->homeDir.'/.aws';
        mkdir($awsDir);
        file_put_contents($awsDir.'/credentials', "[work]\naws_access_key_id=profile-key\naws_secret_access_key=profile-secret\n");

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], ['Updated AWS', 'yes', '', 'work']);

        $this->assertStringContainsString('Would you like to update the authentication method or credentials?', $tester->getDisplay());
        $this->assertStringContainsString('Available AWS credential profiles:', $tester->getDisplay());
        $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }

    /**
     * @dataProvider provideProviderStatuses
     */
    public function testUpdateProviderNameOnly(string $status): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'status' => $status,
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('getProvider');
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, null, 'Updated AWS');

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], ['Updated AWS', 'no']);

        $this->assertStringNotContainsString('Which authentication method would you like to use?', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }

    public function testUpdateProviderNameOnlyNonInteractively(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'name' => 'AWS']);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('getProvider');
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, null, 'Updated AWS');

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(UpdateProviderCommand::NAME));
        $tester->execute(['provider' => '123', 'name' => 'Updated AWS'], ['interactive' => false]);

        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }

    public function testUpdateProviderRejectsBothAuthenticationOptionsNonInteractively(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('The "--aws-profile" and "--role-arn" options cannot be used together');

        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'name' => 'AWS']);
        $detailedProvider = CloudProviderFactory::create(['id' => 123, 'name' => 'AWS']);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]))->ordered();
        $this->apiClient->shouldReceive('getProvider')->once()->with(123)->andReturn($detailedProvider)->ordered();
        $this->apiClient->shouldNotReceive('updateProvider');

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);

        (new CommandTester($this->application->find(UpdateProviderCommand::NAME)))->execute([
            'provider' => '123',
            '--aws-profile' => 'work',
            '--role-arn' => 'arn:aws:iam::444455556666:role/customer-role',
        ], ['interactive' => false]);
    }

    public function testUpdateProviderWithoutChangesDoesNotSendPatch(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'name' => 'AWS']);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('getProvider');
        $this->apiClient->shouldNotReceive('updateProvider');

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], ['', 'no']);

        $this->assertStringContainsString('No changes made', $tester->getDisplay());
    }
}
