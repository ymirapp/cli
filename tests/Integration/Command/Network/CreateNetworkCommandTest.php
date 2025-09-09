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

namespace Ymir\Cli\Tests\Integration\Command\Network;

use Illuminate\Support\Collection;
use Ymir\Cli\Command\Network\CreateNetworkCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateNetworkCommandTest extends TestCase
{
    public function testCreateNetwork(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'aws']);
        $network = NetworkFactory::create([
            'id' => 1,
            'name' => 'my-network',
            'region' => 'us-east-1',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createNetwork')->with($provider, 'my-network', 'us-east-1')->andReturn($network);

        $this->bootApplication([new CreateNetworkCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateNetworkCommand::NAME, [
            'name' => 'my-network',
            '--provider' => '1',
            '--region' => 'us-east-1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Network created', $display);
    }

    public function testCreateNetworkInteractive(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'aws']);
        $network = NetworkFactory::create([
            'id' => 1,
            'name' => 'my-network',
            'region' => 'us-east-1',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createNetwork')->with($provider, 'my-network', 'us-east-1')->andReturn($network);

        $this->bootApplication([new CreateNetworkCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateNetworkCommand::NAME, [], [
            'my-network', // Name
            '1',          // Provider
            'us-east-1',  // Region
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Network created', $display);
    }
}
