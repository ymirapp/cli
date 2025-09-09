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

use Ymir\Cli\Command\Database\ListDatabaseUsersCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\DatabaseUserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListDatabaseUsersCommandTest extends TestCase
{
    public function testListDatabaseUsersWithArgument(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
        ]);

        $user1 = DatabaseUserFactory::create(['id' => 1, 'username' => 'user1']);
        $user2 = DatabaseUserFactory::create(['id' => 2, 'username' => 'user2']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseUsers')->with(\Mockery::on(function ($arg) use ($server) {
            return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
        }))->andReturn(new ResourceCollection([$user1, $user2]));

        $this->bootApplication([new ListDatabaseUsersCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListDatabaseUsersCommand::NAME, ['server' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('user1', $display);
        $this->assertStringContainsString('user2', $display);
    }
}
