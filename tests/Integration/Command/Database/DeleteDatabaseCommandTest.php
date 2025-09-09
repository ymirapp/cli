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

use Ymir\Cli\Command\Database\DeleteDatabaseCommand;
use Ymir\Cli\Resource\Definition\DatabaseDefinition;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDatabaseCommandTest extends TestCase
{
    public function testAbortsDeleteIfNoConfirmation(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'my-server', 'publicly_accessible' => true]);
        $db = DatabaseFactory::create(['name' => 'old_db']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with($server)->andReturn(new ResourceCollection([$db]));
        $this->apiClient->shouldNotReceive('deleteDatabase');

        $this->bootApplication([new DeleteDatabaseCommand($this->apiClient, $this->createExecutionContextFactory([
            Database::class => function () { return new DatabaseDefinition(); },
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseCommand::NAME, ['database' => 'old_db', '--server' => '1'], ['n']);

        $this->assertStringNotContainsString('Database deleted', $tester->getDisplay());
    }

    public function testDeleteDatabaseInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $db = DatabaseFactory::create(['name' => 'interactive_db']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }))->andReturn(new ResourceCollection([$db]));
        $this->apiClient->shouldReceive('deleteDatabase')->once()->with(\Mockery::on(function ($arg) use ($db) {
            return $arg instanceof Database && $arg->getName() === $db->getName();
        }));

        $this->bootApplication([new DeleteDatabaseCommand($this->apiClient, $this->createExecutionContextFactory([
            Database::class => function () { return new DatabaseDefinition(); },
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseCommand::NAME, [], [
            '1', // server choice
            'interactive_db', // database choice
            'y', // confirmation
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to delete a database from?', $display);
        $this->assertStringContainsString('Which database would you like to delete?', $display);
        $this->assertStringContainsString('Are you sure you want to delete the "interactive_db" database?', $display);
        $this->assertStringContainsString('Database deleted', $display);
    }

    public function testDeleteDatabaseWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $db = DatabaseFactory::create(['name' => 'old_db']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }))->andReturn(new ResourceCollection([$db]));
        $this->apiClient->shouldReceive('deleteDatabase')->once()->with(\Mockery::on(function ($arg) use ($db) {
            return $arg instanceof Database && $arg->getName() === $db->getName();
        }));

        $this->bootApplication([new DeleteDatabaseCommand($this->apiClient, $this->createExecutionContextFactory([
            Database::class => function () { return new DatabaseDefinition(); },
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteDatabaseCommand::NAME, [
            'database' => 'old_db',
            '--server' => '1',
        ], ['y']); // confirmation

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Are you sure you want to delete the "old_db" database?', $display);
        $this->assertStringContainsString('Database deleted', $display);
    }
}
