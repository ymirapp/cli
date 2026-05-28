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

use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseFactory
{
    public static function createMysql(array $data = []): Database
    {
        return self::create(array_replace_recursive([
            'database_server' => self::databaseServerData(DatabaseServer::ENGINE_MYSQL, 'mysql'),
        ], $data));
    }

    public static function createPostgresql(array $data = []): Database
    {
        return self::create(array_replace_recursive([
            'database_server' => self::databaseServerData(DatabaseServer::ENGINE_POSTGRESQL, 'postgresql'),
        ], $data));
    }

    private static function create(array $data): Database
    {
        return Database::fromArray(array_replace_recursive([
            'name' => 'database',
        ], $data));
    }

    private static function databaseServerData(string $engine, string $type): array
    {
        return [
            'id' => 1,
            'name' => 'server',
            'region' => 'us-east-1',
            'status' => 'available',
            'publicly_accessible' => true,
            'endpoint' => 'db.example.com',
            'engine' => $engine,
            'locked' => false,
            'type' => $type,
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
        ];
    }
}
