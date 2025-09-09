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

use Ymir\Cli\Command\Database\GetDatabaseServerInfoCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetDatabaseServerInfoCommandTest extends TestCase
{
    public function testGetDatabaseServerInfoInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $this->bootApplication([new GetDatabaseServerInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetDatabaseServerInfoCommand::NAME, [], ['1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to get information about?', $display);
        $this->assertStringContainsString('my-server', $display);
    }

    public function testGetDatabaseServerInfoWithArgument(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'region' => 'us-east-1',
            'status' => 'available',
            'endpoint' => 'db.example.com',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $this->bootApplication([new GetDatabaseServerInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetDatabaseServerInfoCommand::NAME, ['server' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('1', $display);
        $this->assertStringContainsString('my-server', $display);
        $this->assertStringContainsString('us-east-1', $display);
        $this->assertStringContainsString('available', $display);
        $this->assertStringContainsString('db.example.com', $display);
    }
}
