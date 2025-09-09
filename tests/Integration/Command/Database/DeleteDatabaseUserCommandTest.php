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

use Ymir\Cli\Command\Database\DeleteDatabaseUserCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Definition\DatabaseUserDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\DatabaseUserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDatabaseUserCommandTest extends TestCase
{
    public function testDeleteDatabaseUserWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'my-server', 'publicly_accessible' => true]);
        $user = DatabaseUserFactory::create(['id' => 1, 'username' => 'old_user']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseUsers')->with($server)->andReturn(new ResourceCollection([$user]));
        $this->apiClient->shouldReceive('deleteDatabaseUser')->once();

        $this->bootApplication([new DeleteDatabaseUserCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseUserCommand::NAME, [
            'user' => 'old_user',
            '--server' => '1',
        ], ['y']);

        $this->assertStringContainsString('Database user deleted', $tester->getDisplay());
    }

    public function testShowsManualQueryForPrivateServer(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'private-server', 'publicly_accessible' => false]);
        $user = DatabaseUserFactory::create([
            'id' => 1,
            'username' => 'private_user',
            'database_server' => [
                'id' => 1,
                'name' => 'private-server',
                'publicly_accessible' => false,
                'region' => 'us-east-1',
                'status' => 'available',
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
                            'owner' => ['id' => 1, 'name' => 'owner'],
                        ],
                    ],
                ],
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => ['id' => 1, 'name' => 'owner'],
                    ],
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseUsers')->with($server)->andReturn(new ResourceCollection([$user]));
        $this->apiClient->shouldReceive('deleteDatabaseUser')->once();

        $this->bootApplication([new DeleteDatabaseUserCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseUserCommand::NAME, [
            'user' => 'private_user',
            '--server' => '1',
        ], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('needs to be manually deleted on the database server', $display);
        $this->assertStringContainsString('DROP USER IF EXISTS private_user@\'%\'', $display);
    }
}
