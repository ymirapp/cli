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

namespace Ymir\Cli\Tests\Integration\Command\Network;

use Ymir\Cli\Command\Network\RemoveBastionHostCommand;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RemoveBastionHostCommandTest extends TestCase
{
    public function testRemoveBastionHost(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create([
            'id' => 1,
            'name' => 'my-network',
            'bastion_host' => [
                'id' => 1,
                'key_name' => 'name',
                'endpoint' => '1.2.3.4',
                'private_key' => 'key',
                'status' => 'available',
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('removeBastionHost')->with($network)->once();

        $this->bootApplication([new RemoveBastionHostCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(RemoveBastionHostCommand::NAME, ['network' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Bastion host removed', $display);
    }
}
