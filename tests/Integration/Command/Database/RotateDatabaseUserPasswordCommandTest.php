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

use Ymir\Cli\Command\Database\RotateDatabaseUserPasswordCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Definition\DatabaseUserDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\DatabaseUserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RotateDatabaseUserPasswordCommandTest extends TestCase
{
    public function testRotateDatabaseUserPassword(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create(['id' => 1, 'name' => 'my-server', 'publicly_accessible' => true]);
        $user = DatabaseUserFactory::create(['id' => 1, 'username' => 'old_user']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('getDatabaseUsers')->with($server)->andReturn(new ResourceCollection([$user]));
        $this->apiClient->shouldReceive('rotateDatabaseUserPassword')
            ->once()
            ->andReturn(collect(['username' => 'old_user', 'password' => 'new-secret-123']));

        $this->bootApplication([new RotateDatabaseUserPasswordCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            DatabaseUser::class => function () { return new DatabaseUserDefinition(); },
        ]))]);

        $tester = $this->executeCommand(RotateDatabaseUserPasswordCommand::NAME, [
            'user' => 'old_user',
            '--server' => '1',
        ], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database user password rotated successfully', $display);
        $this->assertStringContainsString('new-secret-123', $display);
    }
}
