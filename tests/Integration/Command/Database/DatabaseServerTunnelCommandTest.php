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

use Ymir\Cli\Command\Database\DatabaseServerTunnelCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DatabaseServerTunnelCommandTest extends TestCase
{
    public function testDatabaseServerTunnelInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-private-server',
            'publicly_accessible' => false,
            'status' => 'available',
            'endpoint' => 'db.internal',
            'network' => [
                'id' => 1,
                'name' => 'my-network',
                'region' => 'us-east-1',
                'status' => 'active',
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => [
                            'id' => 1,
                            'name' => 'owner',
                        ],
                    ],
                ],
                'bastion_host' => [
                    'id' => 1,
                    'key_name' => 'bastion',
                    'endpoint' => 'bastion.example.com',
                    'private_key' => 'secret',
                    'status' => 'active',
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $process = \Mockery::mock(Process::class);
        $process->shouldReceive('wait')->once();

        $sshExecutable = \Mockery::mock(SshExecutable::class);
        $sshExecutable->shouldReceive('openTunnelToBastionHost')
            ->once()
            ->andReturn($process);

        $this->bootApplication([new DatabaseServerTunnelCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]), $sshExecutable)]);

        $tester = $this->executeCommand(DatabaseServerTunnelCommand::NAME, [], ['1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Which database server would you like to connect to?', $display);
        $this->assertStringContainsString('SSH tunnel to the "my-private-server" database server opened', $display);
    }

    public function testDatabaseServerTunnelWithArgument(): void
    {
        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'my-private-server',
            'publicly_accessible' => false,
            'status' => 'available',
            'endpoint' => 'db.internal',
            'network' => [
                'id' => 1,
                'name' => 'my-network',
                'region' => 'us-east-1',
                'status' => 'active',
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => [
                            'id' => 1,
                            'name' => 'owner',
                        ],
                    ],
                ],
                'bastion_host' => [
                    'id' => 1,
                    'key_name' => 'bastion',
                    'endpoint' => 'bastion.example.com',
                    'private_key' => 'secret',
                    'status' => 'active',
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $process = \Mockery::mock(Process::class);
        $process->shouldReceive('wait')->once();

        $sshExecutable = \Mockery::mock(SshExecutable::class);
        $sshExecutable->shouldReceive('openTunnelToBastionHost')
            ->once()
            ->with(\Mockery::any(), 3305, 'db.internal', 3306)
            ->andReturn($process);

        $this->bootApplication([new DatabaseServerTunnelCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]), $sshExecutable)]);

        $tester = $this->executeCommand(DatabaseServerTunnelCommand::NAME, ['server' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('SSH tunnel to the "my-private-server" database server opened', $display);
        $this->assertStringContainsString('127.0.0.1:3305', $display);
    }

    public function testThrowsExceptionIfServerIsPublic(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "public-server" database server is publicly accessible and isn\'t on a private subnet');

        $team = $this->setupActiveTeam();

        $server = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'public-server',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $sshExecutable = \Mockery::mock(SshExecutable::class);

        $this->bootApplication([new DatabaseServerTunnelCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
        ]), $sshExecutable)]);

        $this->executeCommand(DatabaseServerTunnelCommand::NAME, ['server' => '1']);
    }
}
