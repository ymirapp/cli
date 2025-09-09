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

use Ymir\Cli\Command\Network\ListNetworksCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListNetworksCommandTest extends TestCase
{
    public function testListNetworks(): void
    {
        $team = $this->setupActiveTeam();

        $network1 = NetworkFactory::create([
            'id' => 1,
            'name' => 'network-1',
            'region' => 'us-east-1',
            'status' => 'available',
            'has_nat_gateway' => true,
        ]);
        $network2 = NetworkFactory::create([
            'id' => 2,
            'name' => 'network-2',
            'region' => 'eu-west-1',
            'status' => 'creating',
            'has_nat_gateway' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network1, $network2]));

        $this->bootApplication([new ListNetworksCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $tester = $this->executeCommand(ListNetworksCommand::NAME);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('1', $display);
        $this->assertStringContainsString('network-1', $display);
        $this->assertStringContainsString('us-east-1', $display);
        $this->assertStringContainsString('available', $display);
        $this->assertStringContainsString('yes', $display); // NAT Gateway

        $this->assertStringContainsString('2', $display);
        $this->assertStringContainsString('network-2', $display);
        $this->assertStringContainsString('eu-west-1', $display);
        $this->assertStringContainsString('creating', $display);
        $this->assertStringContainsString('no', $display); // NAT Gateway
    }
}
