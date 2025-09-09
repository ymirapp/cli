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

use Ymir\Cli\Command\Database\RotateDatabaseServerPasswordCommand;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RotateDatabaseServerPasswordCommandTest extends TestCase
{
    public function testRotateDatabaseServerPassword(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-server',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));
        $this->apiClient->shouldReceive('rotateDatabaseServerPassword')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($server) {
                return $arg instanceof DatabaseServer && $arg->getId() === $server->getId();
            }))
            ->andReturn(collect(['username' => 'new-user', 'password' => 'new-password']));

        $this->bootApplication([new RotateDatabaseServerPasswordCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]))]);

        $tester = $this->executeCommand(RotateDatabaseServerPasswordCommand::NAME, ['server' => '1'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('All projects that use the "my-server" database server with the default user will be unable to connect', $display);
        $this->assertStringContainsString('new-user', $display);
        $this->assertStringContainsString('new-password', $display);
        $this->assertStringContainsString('Database server password rotated successfully', $display);
        $this->assertStringContainsString('You need to redeploy all projects', $display);
    }
}
