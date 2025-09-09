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

use Ymir\Cli\Command\Cache\ModifyCacheCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Definition\CacheClusterDefinition;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ModifyCacheCommandTest extends TestCase
{
    public function testModifyCacheFailsIfSameType(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The cache cluster is already a "cache.t3.micro" type');

        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
            'type' => 'cache.t3.micro',
            'engine' => 'redis',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));
        $this->apiClient->shouldReceive('getCacheTypes')->andReturn(collect([
            'cache.t3.micro' => ['cpu' => 1, 'ram' => 0.5, 'price' => ['redis' => 10, 'valkey' => 8]],
        ]));

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new ModifyCacheCommand($this->apiClient, $contextFactory)]);
        $this->executeCommand(ModifyCacheCommand::NAME, ['cache' => '123'], ['cache.t3.micro']);
    }

    public function testModifyCachePromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
            'type' => 'cache.t3.micro',
            'engine' => 'redis',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));
        $this->apiClient->shouldReceive('getCacheTypes')->andReturn(collect([
            'cache.t3.micro' => ['cpu' => 1, 'ram' => 0.5, 'price' => ['redis' => 10, 'valkey' => 8]],
            'cache.t3.small' => ['cpu' => 2, 'ram' => 1, 'price' => ['redis' => 20, 'valkey' => 16]],
        ]));
        $this->apiClient->shouldReceive('updateCache')->once()->with(\Mockery::on(function ($argument) use ($cache) {
            return $argument instanceof CacheCluster && $argument->getId() === $cache->getId();
        }), 'cache.t3.small');

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new ModifyCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ModifyCacheCommand::NAME, [], ['123', 'cache.t3.small', 'yes']);

        $this->assertStringContainsString('Which cache cluster would you like to modify?', $tester->getDisplay());
        $this->assertStringContainsString('Cache cluster modified', $tester->getDisplay());
    }

    public function testModifyCacheSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
            'type' => 'cache.t3.micro',
            'engine' => 'redis',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));
        $this->apiClient->shouldReceive('getCacheTypes')->andReturn(collect([
            'cache.t3.micro' => ['cpu' => 1, 'ram' => 0.5, 'price' => ['redis' => 10, 'valkey' => 8]],
            'cache.t3.small' => ['cpu' => 2, 'ram' => 1, 'price' => ['redis' => 20, 'valkey' => 16]],
        ]));
        $this->apiClient->shouldReceive('updateCache')->once()->with(\Mockery::on(function ($argument) use ($cache) {
            return $argument instanceof CacheCluster && $argument->getId() === $cache->getId();
        }), 'cache.t3.small');

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new ModifyCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ModifyCacheCommand::NAME, ['cache' => '123'], ['cache.t3.small', 'yes']);

        $this->assertStringContainsString('Cache cluster modified', $tester->getDisplay());
    }
}
