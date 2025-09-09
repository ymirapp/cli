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

namespace Ymir\Cli\Tests\Integration\Command\Cache;

use Ymir\Cli\Command\Cache\CacheTunnelCommand;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Resource\Definition\CacheClusterDefinition;
use Ymir\Cli\Resource\Model\BastionHost;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CacheTunnelCommandTest extends TestCase
{
    public function testCacheTunnelSuccessfully(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();
        $network = NetworkFactory::create([
            'id' => 1,
            'bastion_host' => [
                'id' => 1,
                'key_name' => 'name',
                'endpoint' => '1.2.3.4',
                'private_key' => 'key',
                'status' => 'available',
            ],
        ]);

        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
            'status' => 'available',
            'endpoint' => 'cache.ymir.com',
            'network' => [
                'id' => 1,
                'name' => 'network',
                'region' => 'us-east-1',
                'status' => 'active',
                'provider' => [
                    'id' => 1,
                    'name' => 'provider',
                    'type' => 'aws',
                    'team' => [
                        'id' => 1,
                        'name' => 'team',
                        'owner' => [
                            'id' => 1,
                            'name' => 'owner',
                            'email' => 'support@ymirapp.com',
                        ],
                    ],
                ],
                'bastion_host' => [
                    'id' => 1,
                    'key_name' => 'name',
                    'endpoint' => '1.2.3.4',
                    'private_key' => 'key',
                    'status' => 'available',
                ],
            ],
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));

        $process = \Mockery::mock(Process::class);
        $process->shouldReceive('wait')->once();

        $sshExecutable = \Mockery::mock(SshExecutable::class);
        $sshExecutable->shouldReceive('openTunnelToBastionHost')
                      ->once()
                      ->with(\Mockery::on(function ($argument) {
                          return $argument instanceof BastionHost && 1 === $argument->getId();
                      }), 6378, 'cache.ymir.com', 6379)
                      ->andReturn($process);

        $contextFactory = $this->createExecutionContextFactory([
            CacheCluster::class => function () { return new CacheClusterDefinition(); },
        ]);

        $this->bootApplication([new CacheTunnelCommand($this->apiClient, $contextFactory, $sshExecutable)]);
        $tester = $this->executeCommand(CacheTunnelCommand::NAME, ['cache' => '123']);

        $this->assertStringContainsString('SSH tunnel to the "My Cache" cache cluster opened', $tester->getDisplay());
        $this->assertStringContainsString('Local endpoint: 127.0.0.1:6378', $tester->getDisplay());
    }
}
