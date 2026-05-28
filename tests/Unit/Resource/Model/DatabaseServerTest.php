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

use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
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

    public function testFromArraySetsEngine(): void
    {
        $databaseServer = DatabaseServer::fromArray($this->getDatabaseServerData());

        $this->assertSame(DatabaseServer::ENGINE_MYSQL, $databaseServer->getEngine());
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

    public function testGetDefaultLocalPort(): void
    {
        $this->assertSame(3305, $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL)->getDefaultLocalPort());
        $this->assertSame(5433, $this->createDatabaseServer(DatabaseServer::ENGINE_POSTGRESQL)->getDefaultLocalPort());
    }

    public function testGetDefaultLocalPortThrowsExceptionIfEngineUnsupported(): void
    {
        $this->expectException(UnsupportedDatabaseServerEngineException::class);
        $this->expectExceptionMessage('Unsupported database server engine "unsupported".');

        $this->createDatabaseServer('unsupported')->getDefaultLocalPort();
    }

    public function testGetDefaultPort(): void
    {
        $this->assertSame(3306, $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL)->getDefaultPort());
        $this->assertSame(5432, $this->createDatabaseServer(DatabaseServer::ENGINE_POSTGRESQL)->getDefaultPort());
    }

    public function testGetDefaultPortThrowsExceptionIfEngineUnsupported(): void
    {
        $this->expectException(UnsupportedDatabaseServerEngineException::class);
        $this->expectExceptionMessage('Unsupported database server engine "unsupported".');

        $this->createDatabaseServer('unsupported')->getDefaultPort();
    }

    public function testGetEndpoint(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('endpoint', $databaseServer->getEndpoint());
    }

    public function testGetEngine(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame(DatabaseServer::ENGINE_MYSQL, $databaseServer->getEngine());
    }

    public function testGetEngineLabel(): void
    {
        $this->assertSame('MySQL', $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL)->getEngineLabel());
        $this->assertSame('PostgreSQL', $this->createDatabaseServer(DatabaseServer::ENGINE_POSTGRESQL)->getEngineLabel());
    }

    public function testGetEngineLabels(): void
    {
        $this->assertSame([
            DatabaseServer::ENGINE_MYSQL => 'MySQL',
            DatabaseServer::ENGINE_POSTGRESQL => 'PostgreSQL',
        ], DatabaseServer::getEngineLabels());
    }

    public function testGetEngineLabelThrowsExceptionIfEngineUnsupported(): void
    {
        $this->expectException(UnsupportedDatabaseServerEngineException::class);
        $this->expectExceptionMessage('Unsupported database server engine "unsupported".');

        $this->createDatabaseServer('unsupported')->getEngineLabel();
    }

    public function testGetId(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame(1, $databaseServer->getId());
    }

    public function testGetName(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('name', $databaseServer->getName());
    }

    public function testGetNetwork(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertInstanceOf(Network::class, $databaseServer->getNetwork());
    }

    public function testGetPassword(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('password', $databaseServer->getPassword());
    }

    public function testGetProvider(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertInstanceOf(CloudProvider::class, $databaseServer->getProvider());
    }

    public function testGetRegion(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('region', $databaseServer->getRegion());
    }

    public function testGetStatus(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('status', $databaseServer->getStatus());
    }

    public function testGetStorage(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame(10, $databaseServer->getStorage());
    }

    public function testGetType(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('type', $databaseServer->getType());
    }

    public function testGetUsername(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertSame('username', $databaseServer->getUsername());
    }

    public function testIsAurora(): void
    {
        $this->assertTrue($this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL, DatabaseServer::AURORA_MYSQL_DATABASE_TYPE)->isAurora());
        $this->assertTrue($this->createDatabaseServer(DatabaseServer::ENGINE_POSTGRESQL, DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE)->isAurora());
    }

    public function testIsAuroraType(): void
    {
        $this->assertTrue(DatabaseServer::isAuroraType(DatabaseServer::AURORA_MYSQL_DATABASE_TYPE));
        $this->assertTrue(DatabaseServer::isAuroraType(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE));
        $this->assertFalse(DatabaseServer::isAuroraType('db.t3.micro'));
    }

    public function testIsEngine(): void
    {
        $this->assertTrue(DatabaseServer::isEngine(DatabaseServer::ENGINE_MYSQL));
        $this->assertTrue(DatabaseServer::isEngine(DatabaseServer::ENGINE_POSTGRESQL));
        $this->assertFalse(DatabaseServer::isEngine('unsupported'));
    }

    public function testIsLocked(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertTrue($databaseServer->isLocked());
    }

    public function testIsNotAurora(): void
    {
        $this->assertFalse($this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL)->isAurora());
    }

    public function testIsPublic(): void
    {
        $databaseServer = $this->createDatabaseServer(DatabaseServer::ENGINE_MYSQL);

        $this->assertFalse($databaseServer->isPublic());
    }

    private function createDatabaseServer(string $engine, string $type = 'type'): DatabaseServer
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

        return new DatabaseServer(1, 'name', 'region', 'endpoint', $engine, true, $network, $provider, false, 'status', 10, $type, 'username', 'password');
    }

    private function getDatabaseServerData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'endpoint' => 'endpoint',
            'engine' => DatabaseServer::ENGINE_MYSQL,
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
