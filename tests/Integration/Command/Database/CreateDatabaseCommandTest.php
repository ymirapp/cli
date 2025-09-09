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

use Ymir\Cli\Command\Database\CreateDatabaseCommand;
use Ymir\Cli\Resource\Definition\DatabaseDefinition;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateDatabaseCommandTest extends TestCase
{
    public function testCreateDatabaseInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('createDatabase')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }), 'interactive_db')->andReturn(DatabaseFactory::create(['name' => 'interactive_db']));

        $this->bootApplication([new CreateDatabaseCommand($this->apiClient, $this->createExecutionContextFactory([
            Database::class => function () { return new DatabaseDefinition(); },
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseCommand::NAME, [], [
            '1', // server choice
            'interactive_db', // database name
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server should the database be created on?', $display);
        $this->assertStringContainsString('What is the name of the database being created?', $display);
        $this->assertStringContainsString('Database created', $display);
    }

    public function testCreateDatabaseWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('createDatabase')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }), 'new_db')->andReturn(DatabaseFactory::create(['name' => 'new_db']));

        $this->bootApplication([new CreateDatabaseCommand($this->apiClient, $this->createExecutionContextFactory([
            Database::class => function () { return new DatabaseDefinition(); },
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseCommand::NAME, [
            'database' => 'new_db',
            '--server' => '1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database created', $display);
    }
}
