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

use Ymir\Cli\Command\Database\ListDatabasesCommand;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListDatabasesCommandTest extends TestCase
{
    public function testListDatabasesInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $db1 = DatabaseFactory::create(['name' => 'db1']);

        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }))->andReturn(new ResourceCollection([$db1]));

        $this->bootApplication([new ListDatabasesCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListDatabasesCommand::NAME, [], ['1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to list databases from?', $display);
        $this->assertStringContainsString('db1', $display);
    }

    public function testListDatabasesWithArgument(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'publicly_accessible' => true,
        ]);

        $db1 = DatabaseFactory::create(['name' => 'db1']);
        $db2 = DatabaseFactory::create(['name' => 'db2']);

        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabases')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }))->andReturn(new ResourceCollection([$db1, $db2]));

        $this->bootApplication([new ListDatabasesCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListDatabasesCommand::NAME, ['server' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('db1', $display);
        $this->assertStringContainsString('db2', $display);
    }

    public function testThrowsExceptionIfServerIsPrivate(): void
    {
        $this->expectException(ResourceStateException::class);
        $this->expectExceptionMessage('Cannot list databases on a private database server');

        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'private-server',
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $this->bootApplication([new ListDatabasesCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $this->executeCommand(ListDatabasesCommand::NAME, ['server' => '1']);
    }
}
