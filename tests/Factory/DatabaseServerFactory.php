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

use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseServerFactory
{
    public static function create(array $data = []): DatabaseServer
    {
        return DatabaseServer::fromArray(array_merge([
            'id' => 1,
            'name' => 'db-server',
            'region' => 'us-east-1',
            'status' => 'available',
            'publicly_accessible' => true,
            'endpoint' => 'db.example.com',
            'locked' => false,
            'type' => 'mysql',
            'storage' => 10,
            'network' => [
                'id' => 1,
                'name' => 'network',
                'region' => 'us-east-1',
                'status' => 'active',
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => [
                            'id' => 1,
                            'name' => 'owner',
                        ],
                    ],
                ],
            ],
            'provider' => [
                'id' => 1,
                'name' => 'provider',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => [
                        'id' => 1,
                        'name' => 'owner',
                    ],
                ],
            ],
        ], $data));
    }
}
