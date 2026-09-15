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
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\Resource\ProvisioningFailedException;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;
use Ymir\Sdk\Exception\ClientException;

class ConnectProviderCommandTest extends TestCase
{
    public function testConnectProviderDoesNotRetryCreationIfAccessKeyUpdateFails(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'status' => 'pending']);
        $response = new Response(422, [], '{"errors":{"credentials":["Invalid AWS credentials"]}}');
        $clientException = new ClientException(new GuzzleClientException('Invalid update', new Request('PATCH', '/providers/123'), $response));

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'key', 'secret' => 'secret'])->andThrow($clientException)->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = new CommandTester($this->application->find(ConnectProviderCommand::NAME));
        $tester->setInputs(['AWS', '1', 'key', 'secret', 'no']);

        try {
            $tester->execute([]);

            $this->fail('The failed credential update should stop the command');
        } catch (CommandCancelledException $exception) {
            $this->assertSame(130, $exception->getCode());
        }

        $this->assertStringContainsString('Failed to connect the pending cloud provider (ID: 123)', $tester->getDisplay());
        $this->assertStringContainsString('Invalid AWS credentials', $tester->getDisplay());
        $this->assertStringContainsString('provider:update 123', $tester->getDisplay());
        $this->assertStringContainsString('provider:delete 123', $tester->getDisplay());
        $this->assertStringContainsString('Failed to finalize the cloud provider. Do you want to retry?', $tester->getDisplay());
        $this->assertStringNotContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderDoesNotRetryCreationIfAssumeRoleUpdateFails(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'status' => 'pending',
            'authentication' => [
                'method' => null,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'ymir-cloud-provider-123',
                ],
            ],
        ]);
        $response = new Response(422, [], '{"errors":{"credentials":["Invalid AWS role"]}}');
        $clientException = new ClientException(new GuzzleClientException('Invalid update', new Request('PATCH', '/providers/123'), $response));

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'])->andThrow($clientException)->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = new CommandTester($this->application->find(ConnectProviderCommand::NAME));
        $tester->setInputs(['AWS', '', 'arn:aws:iam::444455556666:role/customer-role', 'no']);

        try {
            $tester->execute([]);

            $this->fail('The failed role update should stop the command');
        } catch (CommandCancelledException $exception) {
            $this->assertSame(130, $exception->getCode());
        }

        $this->assertStringContainsString('Failed to connect the pending cloud provider (ID: 123)', $tester->getDisplay());
        $this->assertStringContainsString('Invalid AWS role', $tester->getDisplay());
        $this->assertStringContainsString('provider:update 123', $tester->getDisplay());
        $this->assertStringContainsString('provider:delete 123', $tester->getDisplay());
        $this->assertStringContainsString('Failed to finalize the cloud provider. Do you want to retry?', $tester->getDisplay());
        $this->assertStringNotContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderReportsPendingProviderIfAssumeRoleSetupIsMissing(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'status' => 'pending', 'authentication' => []]);

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider);
        $this->apiClient->shouldNotReceive('updateProvider');

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = new CommandTester($this->application->find(ConnectProviderCommand::NAME));
        $tester->setInputs(['AWS', '']);

        try {
            $tester->execute([]);

            $this->fail('Missing AssumeRole setup values should stop the command');
        } catch (ProvisioningFailedException $exception) {
            $this->assertStringContainsString('Failed to prepare authentication for the pending cloud provider (ID: 123)', $exception->getMessage());
            $this->assertStringContainsString('the Ymir API did not return the setup values needed to create the IAM role', $exception->getMessage());
            $this->assertStringContainsString('provider:update 123', $exception->getMessage());
            $this->assertStringContainsString('provider:delete 123', $exception->getMessage());
        }

        $this->assertStringNotContainsString('Do you want to retry?', $tester->getDisplay());
        $this->assertStringNotContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderRequiresAwsProfileNonInteractively(): void
    {
        $this->setupActiveTeam();

        $this->apiClient->shouldNotReceive('updateProvider');
        $this->apiClient->shouldNotReceive('createProvider');

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(ConnectProviderCommand::NAME));

        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('You must enter the "--aws-profile" option when configuring cloud provider authentication non-interactively');

        $tester->execute([], ['interactive' => false]);
    }

    public function testConnectProviderRetriesAssumeRoleUpdateWithoutCreatingAnotherProvider(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'status' => 'pending',
            'authentication' => [
                'method' => null,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'ymir-cloud-provider-123',
                ],
            ],
        ]);
        $response = new Response(503, [], '{"message":"Unable to validate the cloud provider role right now."}');
        $serverException = new GuzzleServerException('Unable to validate the cloud provider role right now.', new Request('PATCH', '/providers/123'), $response);
        $credentials = ['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'];

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, $credentials)->andThrow($serverException)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, $credentials)->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', '', $credentials['role_arn'], 'yes', $credentials['role_arn']]);

        $this->assertStringContainsString('Failed to connect the pending cloud provider (ID: 123)', $tester->getDisplay());
        $this->assertStringContainsString('provider:update 123', $tester->getDisplay());
        $this->assertStringContainsString('Failed to finalize the cloud provider. Do you want to retry?', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyNonInteractivelyWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'name' => 'Automation AWS', 'status' => 'pending']);

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'Automation AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'profile-key', 'secret' => 'profile-secret'])->ordered();

        $awsDir = $this->homeDir.'/.aws';
        mkdir($awsDir);
        file_put_contents($awsDir.'/credentials', "[work]\naws_access_key_id=profile-key\naws_secret_access_key=profile-secret\n");

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(ConnectProviderCommand::NAME));
        $tester->execute(['name' => 'Automation AWS', '--aws-profile' => 'work'], ['interactive' => false]);

        $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
        $this->assertStringNotContainsString('Which authentication method would you like to use?', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'profile-key', 'secret' => 'profile-secret'])->ordered();

        $awsDir = $this->homeDir.'/.aws';
        mkdir($awsDir);
        file_put_contents($awsDir.'/credentials', "[default]\naws_access_key_id=profile-key\naws_secret_access_key=profile-secret\n");

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', '1', 'default']);

        $this->assertStringContainsString('Available AWS credential profiles:', $tester->getDisplay());
        $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyWithCustomName(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['name' => 'Custom AWS']);

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'Custom AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'key', 'secret' => 'secret'])->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['Custom AWS', '1', 'key', 'secret']);

        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyWithDefaultNameAndAssumeRole(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'status' => 'pending',
            'authentication' => [
                'method' => null,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'])->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['', '', 'arn:aws:iam::444455556666:role/customer-role']);

        $this->assertStringContainsString('IAM role (recommended)', $tester->getDisplay());
        $this->assertStringContainsString('Access key (legacy, less secure)', $tester->getDisplay());
        $this->assertStringContainsString('Ymir AWS account ID: 111122223333', $tester->getDisplay());
        $this->assertStringContainsString('External ID: server-external-id', $tester->getDisplay());
        $this->assertStringContainsString('Role name: server-role-name', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyWithManualCredentials(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'key', 'secret' => 'secret'])->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', '1', 'key', 'secret']);

        $this->assertStringContainsString('Access keys are a legacy, less-secure authentication method', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }
}
