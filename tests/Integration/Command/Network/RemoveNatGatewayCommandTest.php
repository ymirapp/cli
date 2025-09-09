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

use Ymir\Cli\Command\Network\RemoveNatGatewayCommand;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RemoveNatGatewayCommandTest extends TestCase
{
    public function testRemoveNatGateway(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network', 'has_nat_gateway' => true]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('removeNatGateway')->with($network)->once();

        $this->bootApplication([new RemoveNatGatewayCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(RemoveNatGatewayCommand::NAME, ['network' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('NAT gateway removed', $display);
    }
}
