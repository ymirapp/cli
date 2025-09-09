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

use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function testFromArraySetsDatabaseServer(): void
    {
        $database = Database::fromArray($this->getDatabaseData());

        $this->assertSame(1, $database->getDatabaseServer()->getId());
    }

    public function testFromArraySetsId(): void
    {
        $database = Database::fromArray($this->getDatabaseData());

        $this->assertSame(0, $database->getId());
    }

    public function testFromArraySetsName(): void
    {
        $database = Database::fromArray($this->getDatabaseData());

        $this->assertSame('db_name', $database->getName());
    }

    public function testGetDatabaseServer(): void
    {
        $database = $this->createDatabase();

        $this->assertInstanceOf(DatabaseServer::class, $database->getDatabaseServer());
    }

    public function testGetId(): void
    {
        $database = $this->createDatabase();

        $this->assertSame(0, $database->getId());
    }

    public function testGetName(): void
    {
        $database = $this->createDatabase();

        $this->assertSame('db_name', $database->getName());
    }

    private function createDatabase(): Database
    {
        $databaseServer = DatabaseServer::fromArray([
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'endpoint' => 'endpoint',
            'locked' => true,
            'publicly_accessible' => false,
            'status' => 'status',
            'storage' => 10,
            'type' => 'type',
            'network' => [
                'id' => 5,
                'name' => 'network',
                'region' => 'region',
                'status' => 'status',
                'provider' => [
                    'id' => 2,
                    'name' => 'provider',
                    'team' => [
                        'id' => 3,
                        'name' => 'team',
                        'owner' => [
                            'id' => 4,
                            'name' => 'owner',
                        ],
                    ],
                ],
            ],
            'provider' => [
                'id' => 2,
                'name' => 'provider',
                'team' => [
                    'id' => 3,
                    'name' => 'team',
                    'owner' => [
                        'id' => 4,
                        'name' => 'owner',
                    ],
                ],
            ],
        ]);

        return new Database('db_name', $databaseServer);
    }

    private function getDatabaseData(): array
    {
        return [
            'name' => 'db_name',
            'database_server' => [
                'id' => 1,
                'name' => 'name',
                'region' => 'region',
                'endpoint' => 'endpoint',
                'locked' => true,
                'publicly_accessible' => false,
                'status' => 'status',
                'storage' => 10,
                'type' => 'type',
                'network' => [
                    'id' => 5,
                    'name' => 'network',
                    'region' => 'region',
                    'status' => 'status',
                    'provider' => [
                        'id' => 2,
                        'name' => 'provider',
                        'team' => [
                            'id' => 3,
                            'name' => 'team',
                            'owner' => [
                                'id' => 4,
                                'name' => 'owner',
                            ],
                        ],
                    ],
                ],
                'provider' => [
                    'id' => 2,
                    'name' => 'provider',
                    'team' => [
                        'id' => 3,
                        'name' => 'team',
                        'owner' => [
                            'id' => 4,
                            'name' => 'owner',
                        ],
                    ],
                ],
            ],
        ];
    }
}
