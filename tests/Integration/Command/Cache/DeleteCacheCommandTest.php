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

use Ymir\Cli\Command\Cache\DeleteCacheCommand;
use Ymir\Cli\Resource\Definition\CacheClusterDefinition;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteCacheCommandTest extends TestCase
{
    public function testDeleteCacheCancelled(): void
    {
        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));
        $this->apiClient->shouldNotReceive('deleteCache');

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new DeleteCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteCacheCommand::NAME, ['cache' => '123'], ['no']);

        $this->assertStringNotContainsString('Cache cluster deleted', $tester->getDisplay());
    }

    public function testDeleteCacheSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));
        $this->apiClient->shouldReceive('deleteCache')->once()->with(\Mockery::on(function ($argument) use ($cache) {
            return $argument instanceof CacheCluster && $argument->getId() === $cache->getId();
        }));

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new DeleteCacheCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteCacheCommand::NAME, ['cache' => '123'], ['yes']);

        $this->assertStringContainsString('Cache cluster deleted', $tester->getDisplay());
    }
}
