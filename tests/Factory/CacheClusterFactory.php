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

namespace Ymir\Cli\Tests\Factory;

use Ymir\Cli\Resource\Model\CacheCluster;

class CacheClusterFactory
{
    public static function create(array $data = []): CacheCluster
    {
        return CacheCluster::fromArray(array_merge([
            'id' => 1,
            'name' => 'cache',
            'region' => 'us-east-1',
            'status' => 'available',
            'endpoint' => 'cache.ymir.com',
            'engine' => 'redis',
            'type' => 'cache.t3.micro',
            'network' => [
                'id' => 1,
                'name' => 'network',
                'region' => 'us-east-1',
                'status' => 'active',
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'type' => 'aws',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => [
                            'id' => 1,
                            'name' => 'owner',
                            'email' => 'support@ymirapp.com',
                        ],
                    ],
                ],
            ],
        ], $data));
    }
}
