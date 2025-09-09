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

namespace Ymir\Cli\Tests\Integration\Command\Database;

use Ymir\Cli\Command\Database\CreateDatabaseUserCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Definition\DatabaseUserDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\DatabaseUserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateDatabaseUserCommandTest extends TestCase
{
    public function testCreateDatabaseUserInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'my-server', 'publicly_accessible' => true]);
        $db1 = DatabaseFactory::create(['name' => 'db1']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with($server)->andReturn(new ResourceCollection([$db1]));
        $this->apiClient->shouldReceive('createDatabaseUser')->once()->andReturn(DatabaseUserFactory::create());

        $this->bootApplication([new CreateDatabaseUserCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseUserCommand::NAME, [], [
            '1', // server choice
            'new_user', // username
            'n', // do you want access to all databases?
            '0', // database choice (db1)
            '', // end database selection
        ]);

        $this->assertStringContainsString('Database user created successfully', $tester->getDisplay());
    }

    public function testCreateDatabaseUserWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('createDatabaseUser')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($server) {
                return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
            }), 'new_user', ['db1', 'db2'])
            ->andReturn(DatabaseUserFactory::create(['username' => 'new_user', 'password' => 'secret123']));

        $this->bootApplication([new CreateDatabaseUserCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseUserCommand::NAME, [
            'user' => 'new_user',
            'databases' => ['db1', 'db2'],
            '--server' => '1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database user created successfully', $display);
        $this->assertStringContainsString('new_user', $display);
        $this->assertStringContainsString('secret123', $display);
    }

    public function testShowsManualQueriesForPrivateServer(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'private-server',
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('createDatabaseUser')
            ->once()
            ->andReturn(DatabaseUserFactory::create([
                'username' => 'private_user',
                'password' => 'pass123',
                'database_server' => [
                    'id' => 1,
                    'name' => 'private-server',
                    'region' => 'us-east-1',
                    'status' => 'available',
                    'publicly_accessible' => false,
                    'endpoint' => 'db.internal',
                    'locked' => false,
                    'type' => 'mysql',
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
                ],
            ]));

        $this->bootApplication([new CreateDatabaseUserCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseUserCommand::NAME, [
            'user' => 'private_user',
            'databases' => ['db1'],
            '--server' => '1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('needs to be manually created on the "private-server" database server', $display);
        $this->assertStringContainsString('CREATE USER private_user@\'%\' IDENTIFIED BY \'pass123\'', $display);
    }
}
