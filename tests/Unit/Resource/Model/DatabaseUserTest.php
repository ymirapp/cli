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

use Carbon\Carbon;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;
use Ymir\Cli\Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    public function testFromArraySetsCreatedAt(): void
    {
        $databaseUser = DatabaseUser::fromArray($this->getDatabaseUserData());

        $this->assertSame('2021-01-01 00:00:00', $databaseUser->getCreatedAt()->toDateTimeString());
    }

    public function testFromArraySetsDatabaseServer(): void
    {
        $databaseUser = DatabaseUser::fromArray($this->getDatabaseUserData());

        $this->assertSame(1, $databaseUser->getDatabaseServer()->getId());
    }

    public function testFromArraySetsId(): void
    {
        $databaseUser = DatabaseUser::fromArray($this->getDatabaseUserData());

        $this->assertSame(1, $databaseUser->getId());
    }

    public function testFromArraySetsName(): void
    {
        $databaseUser = DatabaseUser::fromArray($this->getDatabaseUserData());

        $this->assertSame('username', $databaseUser->getName());
    }

    public function testFromArraySetsPassword(): void
    {
        $databaseUser = DatabaseUser::fromArray($this->getDatabaseUserData());

        $this->assertSame('password', $databaseUser->getPassword());
    }

    public function testGetCreatedAt(): void
    {
        $createdAt = Carbon::now();
        $databaseUser = $this->createDatabaseUser($createdAt);

        $this->assertSame($createdAt, $databaseUser->getCreatedAt());
    }

    public function testGetDatabaseServer(): void
    {
        $databaseUser = $this->createDatabaseUser();

        $this->assertInstanceOf(DatabaseServer::class, $databaseUser->getDatabaseServer());
    }

    public function testGetId(): void
    {
        $databaseUser = $this->createDatabaseUser();

        $this->assertSame(1, $databaseUser->getId());
    }

    public function testGetName(): void
    {
        $databaseUser = $this->createDatabaseUser();

        $this->assertSame('username', $databaseUser->getName());
    }

    public function testGetPassword(): void
    {
        $databaseUser = $this->createDatabaseUser();

        $this->assertSame('password', $databaseUser->getPassword());
    }

    private function createDatabaseUser(?Carbon $createdAt = null): DatabaseUser
    {
        if (null === $createdAt) {
            $createdAt = Carbon::now();
        }

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

        return new DatabaseUser(1, $createdAt, $databaseServer, 'username', 'password');
    }

    private function getDatabaseUserData(): array
    {
        return [
            'id' => 1,
            'created_at' => '2021-01-01 00:00:00',
            'username' => 'username',
            'password' => 'password',
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
