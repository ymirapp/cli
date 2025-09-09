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

use Ymir\Cli\Command\Database\DeleteDatabaseServerCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDatabaseServerCommandTest extends TestCase
{
    public function testDeleteDatabaseServerInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('deleteDatabaseServer')->once();

        $this->bootApplication([new DeleteDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseServerCommand::NAME, [], ['1', 'y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to delete?', $display);
        $this->assertStringContainsString('Database server deleted', $display);
    }

    public function testDeleteDatabaseServerWithArgument(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('deleteDatabaseServer')->once()->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }));

        $this->bootApplication([new DeleteDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseServerCommand::NAME, ['server' => '1'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Are you sure you want to delete the "my-server" database server?', $display);
        $this->assertStringContainsString('Database server deleted', $display);
    }

    public function testShowsNatGatewayNoteForPrivateServer(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'private-server',
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('deleteDatabaseServer')->once();

        $this->bootApplication([new DeleteDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseServerCommand::NAME, ['server' => '1'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('If you have no other resources using the private subnet, you should remove the network\'s NAT gateway', $display);
    }
}
