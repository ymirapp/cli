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

use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerTest extends TestCase
{
    public function testFromArraySetsEndpoint(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('endpoint', $databaseServer->getEndpoint());
    }

    public function testFromArraySetsId(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame(1, $databaseServer->getId());
    }

    public function testFromArraySetsIsLocked(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertTrue($databaseServer->isLocked());
    }

    public function testFromArraySetsIsPublic(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertFalse($databaseServer->isPublic());
    }

    public function testFromArraySetsName(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('name', $databaseServer->getName());
    }

    public function testFromArraySetsNetwork(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame(5, $databaseServer->getNetwork()->getId());
    }

    public function testFromArraySetsPassword(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('password', $databaseServer->getPassword());
    }

    public function testFromArraySetsProvider(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame(2, $databaseServer->getProvider()->getId());
    }

    public function testFromArraySetsRegion(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('region', $databaseServer->getRegion());
    }

    public function testFromArraySetsStatus(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('status', $databaseServer->getStatus());
    }

    public function testFromArraySetsStorage(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame(10, $databaseServer->getStorage());
    }

    public function testFromArraySetsType(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('type', $databaseServer->getType());
    }

    public function testFromArraySetsUsername(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame('username', $databaseServer->getUsername());
    }

    public function testGetEndpoint(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('endpoint', $databaseServer->getEndpoint());
    }

    public function testGetId(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame(1, $databaseServer->getId());
    }

    public function testGetName(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('name', $databaseServer->getName());
    }

    public function testGetNetwork(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertInstanceOf(Network::class, $databaseServer->getNetwork());
    }

    public function testGetPassword(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('password', $databaseServer->getPassword());
    }

    public function testGetProvider(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertInstanceOf(CloudProvider::class, $databaseServer->getProvider());
    }

    public function testGetRegion(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('region', $databaseServer->getRegion());
    }

    public function testGetStatus(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('status', $databaseServer->getStatus());
    }

    public function testGetStorage(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame(10, $databaseServer->getStorage());
    }

    public function testGetType(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('type', $databaseServer->getType());
    }

    public function testGetUsername(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertSame('username', $databaseServer->getUsername());
    }

    public function testIsLocked(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertTrue($databaseServer->isLocked());
    }

    public function testIsPublic(): void
    {
        $databaseServer = $this->createDatabaseServer();

        $this->assertFalse($databaseServer->isPublic());
    }

    private function createDatabaseServer(): DatabaseServer
    {
        $user = new User(4, 'owner');
        $team = new Team(3, 'team', $user);
        $provider = new CloudProvider(2, 'provider', $team);
        $network = Network::fromArray([
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
        ]);

        return new DatabaseServer(1, 'name', 'region', 'endpoint', true, $network, $provider, false, 'status', 10, 'type', 'username', 'password');
    }

    private function getDatabaseServerData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'endpoint' => 'endpoint',
            'locked' => true,
            'publicly_accessible' => false,
            'status' => 'status',
            'storage' => 10,
            'type' => 'type',
            'username' => 'username',
            'password' => 'password',
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
        ];
    }
}
