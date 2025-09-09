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

namespace Ymir\Cli\Tests\Integration\Command\Cache;

use Ymir\Cli\Command\Cache\CreateCacheCommand;
use Ymir\Cli\Resource\Definition\CacheClusterDefinition;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateCacheCommandTest extends TestCase
{
    public function testCreateCachePromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $network = NetworkFactory::create(['id' => 1, 'name' => 'network']);

        $cache = CacheClusterFactory::create();

        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('getCacheTypes')->andReturn(collect([
            'cache.t3.micro' => ['cpu' => 1, 'ram' => 0.5, 'price' => ['redis' => 10, 'valkey' => 8]],
        ]));
        $this->apiClient->shouldReceive('createCache')->with(\Mockery::on(function ($argument) use ($network) {
            return $argument instanceof Network && $argument->getId() === $network->getId();
        }), 'my-cache', 'valkey', 'cache.t3.micro')->andReturn($cache);

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new CreateCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateCacheCommand::NAME, [], ['my-cache', '1', 'yes', 'cache.t3.micro']);

        $this->assertStringContainsString('What is the name of the cache cluster', $tester->getDisplay());
        $this->assertStringContainsString('Cache cluster created', $tester->getDisplay());
    }

    public function testCreateCacheSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $network = NetworkFactory::create(['id' => 1, 'name' => 'network']);

        $cache = CacheClusterFactory::create();

        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('getCacheTypes')->andReturn(collect([
            'cache.t3.micro' => ['cpu' => 1, 'ram' => 0.5, 'price' => ['redis' => 10, 'valkey' => 8]],
        ]));
        $this->apiClient->shouldReceive('createCache')->with(\Mockery::on(function ($argument) use ($network) {
            return $argument instanceof Network && $argument->getId() === $network->getId();
        }), 'my-cache', 'redis', 'cache.t3.micro')->andReturn($cache);

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new CreateCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateCacheCommand::NAME, ['name' => 'my-cache', '--network' => 'network', '--engine' => 'redis'], ['yes', 'cache.t3.micro']);

        $this->assertStringContainsString('Cache cluster created', $tester->getDisplay());
    }
}
