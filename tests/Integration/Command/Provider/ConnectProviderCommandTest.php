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

use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ConnectProviderCommandTest extends TestCase
{
    public function testConnectProviderSuccessfullyWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->with($team, 'AWS', ['key' => 'profile-key', 'secret' => 'profile-secret'])->andReturn($provider);

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

        $this->apiClient->shouldReceive('createProvider')->with($team, 'Custom AWS', ['key' => 'key', 'secret' => 'secret'])->andReturn($provider);

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

        $this->apiClient->shouldReceive('createProvider')->with($team, 'AWS', ['key' => 'key', 'secret' => 'secret'])->andReturn($provider);

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

        $this->apiClient->shouldReceive('createProvider')->with($team, 'AWS', ['key' => 'key', 'secret' => 'secret'])->andReturn($provider);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new ConnectProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ConnectProviderCommand::NAME, [], ['AWS', 'key', 'secret']);

        $this->assertStringContainsString('Cloud provider connected', $tester->getDisplay());
    }
}
