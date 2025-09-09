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

use Ymir\Cli\Command\Database\ModifyDatabaseServerCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ModifyDatabaseServerCommandTest extends TestCase
{
    public function testModifyDatabaseServerInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'type' => 'db.t3.micro',
            'storage' => 20,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseServerTypes')->andReturn(collect(['db.t3.micro' => 'db.t3.micro', 'db.t3.small' => 'db.t3.small']));
        $this->apiClient->shouldReceive('updateDatabaseServer')->once();

        $this->bootApplication([new ModifyDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ModifyDatabaseServerCommand::NAME, [], [
            '1', // server choice
            '40', // storage
            'y', // storage confirmation
            'db.t3.small', // type choice
            'y', // type confirmation
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to modify?', $display);
        $this->assertStringContainsString('Database server modified', $display);
    }

    public function testModifyDatabaseServerWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'type' => 'db.t3.micro',
            'storage' => 20,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseServerTypes')->andReturn(collect(['db.t3.micro' => 'db.t3.micro', 'db.t3.small' => 'db.t3.small']));
        $this->apiClient->shouldReceive('updateDatabaseServer')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($server) {
                return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
            }), 40, 'db.t3.small');

        $this->bootApplication([new ModifyDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ModifyDatabaseServerCommand::NAME, [
            'server' => '1',
            '--storage' => '40',
            '--type' => 'db.t3.small',
        ], ['y', 'y']); // confirmations for storage and type changes

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Modifying the database server storage is an irreversible change', $display);
        $this->assertStringContainsString('Modifying the database server type will cause your database to become unavailable', $display);
        $this->assertStringContainsString('Database server modified', $display);
    }

    public function testThrowsExceptionIfReducingStorage(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You cannot reduce the maximum amount of storage allocated to the database server');

        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'my-server', 'storage' => 40]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $this->bootApplication([new ModifyDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $this->executeCommand(ModifyDatabaseServerCommand::NAME, ['server' => '1', '--storage' => '20']);
    }
}
