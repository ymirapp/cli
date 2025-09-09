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

use Ymir\Cli\Command\Database\LockDatabaseServerCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class LockDatabaseServerCommandTest extends TestCase
{
    public function testLockDatabaseServer(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
            'locked' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('changeDatabaseServerLock')->once()->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }), true);

        $this->bootApplication([new LockDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(LockDatabaseServerCommand::NAME, ['server' => '1']);

        $this->assertStringContainsString('Database server locked', $tester->getDisplay());
    }
}
