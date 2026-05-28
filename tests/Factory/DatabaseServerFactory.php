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
    public static function createMysql(array $data = []): DatabaseServer
    {
        return self::create(array_replace_recursive([
            'engine' => DatabaseServer::ENGINE_MYSQL,
            'type' => 'mysql',
        ], $data));
    }

    public static function createPostgresql(array $data = []): DatabaseServer
    {
        return self::create(array_replace_recursive([
            'engine' => DatabaseServer::ENGINE_POSTGRESQL,
            'type' => 'postgresql',
        ], $data));
    }

    public static function createUnsupportedEngine(array $data = []): DatabaseServer
    {
        return self::create(array_replace_recursive([
            'engine' => 'unsupported',
            'type' => 'type',
        ], $data));
    }

    private static function create(array $data): DatabaseServer
    {
        return DatabaseServer::fromArray(array_replace_recursive([
            'id' => 1,
            'name' => 'db-server',
            'region' => 'us-east-1',
            'status' => 'available',
            'publicly_accessible' => true,
            'endpoint' => 'db.example.com',
            'locked' => false,
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
