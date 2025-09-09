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

namespace Ymir\Cli\Tests\Unit\Resource\Model;

use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Tests\TestCase;

class CacheClusterTest extends TestCase
{
    public function testGetEndpoint(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('endpoint', $cacheCluster->getEndpoint());
    }

    public function testGetEngine(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('engine', $cacheCluster->getEngine());
    }

    public function testGetId(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame(1, $cacheCluster->getId());
    }

    public function testGetName(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('name', $cacheCluster->getName());
    }

    public function testGetNetwork(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame(2, $cacheCluster->getNetwork()->getId());
    }

    public function testGetRegion(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('region', $cacheCluster->getRegion());
    }

    public function testGetStatus(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('status', $cacheCluster->getStatus());
    }

    public function testGetType(): void
    {
        $cacheCluster = CacheCluster::fromArray($this->getCacheClusterData());

        $this->assertSame('type', $cacheCluster->getType());
    }

    private function getCacheClusterData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'status' => 'status',
            'endpoint' => 'endpoint',
            'engine' => 'engine',
            'type' => 'type',
            'network' => [
                'id' => 2,
                'name' => 'network',
                'region' => 'region',
                'status' => 'status',
                'provider' => [
                    'id' => 3,
                    'name' => 'provider',
                    'team' => [
                        'id' => 4,
                        'name' => 'team',
                        'owner' => [
                            'id' => 5,
                            'name' => 'owner',
                        ],
                    ],
                ],
            ],
        ];
    }
}
