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
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Exception\Resource\ProvisioningFailedException;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;
use Ymir\Sdk\Exception\ClientException;

class ConnectProviderCommandTest extends TestCase
{
    public function testConnectProviderDoesNotRetryCreationIfCredentialUpdateFails(): void
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
        $tester->setInputs(['AWS', 'key', 'secret']);

        try {
            $tester->execute([]);

            $this->fail('The failed credential update should stop the command');
        } catch (ProvisioningFailedException $exception) {
            $this->assertStringContainsString('Failed to connect the pending cloud provider (ID: 123)', $exception->getMessage());
            $this->assertStringContainsString('Invalid AWS credentials', $exception->getMessage());
            $this->assertStringContainsString('provider:update 123', $exception->getMessage());
            $this->assertStringContainsString('provider:delete 123', $exception->getMessage());
        }

        $this->assertStringNotContainsString('Do you want to retry?', $tester->getDisplay());
        $this->assertStringNotContainsString('Cloud provider connected', $tester->getDisplay());
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
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', 'default']);

        $this->assertStringContainsString('Available AWS credential profiles:', $tester->getDisplay());
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
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['Custom AWS', 'key', 'secret']);

        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }

    public function testConnectProviderSuccessfullyWithDefaultName(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->once()->with($team, 'AWS')->andReturn($provider)->ordered();
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'key', 'secret' => 'secret'])->ordered();

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['', 'key', 'secret']);

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
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', 'key', 'secret']);

        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }
}
