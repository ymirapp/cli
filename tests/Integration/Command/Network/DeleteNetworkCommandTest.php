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

use Ymir\Cli\Command\Network\DeleteNetworkCommand;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteNetworkCommandTest extends TestCase
{
    public function testDeleteNetworkCancelled(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldNotReceive('deleteNetwork');

        $this->bootApplication([new DeleteNetworkCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteNetworkCommand::NAME, ['network' => '1'], ['n']);

        $display = $tester->getDisplay();

        $this->assertStringNotContainsString('Network deleted', $display);
    }

    public function testDeleteNetworkWithId(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('deleteNetwork')->with($network)->once();

        $this->bootApplication([new DeleteNetworkCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteNetworkCommand::NAME, ['network' => '1'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Network deleted', $display);
    }

    public function testDeleteNetworkWithName(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('deleteNetwork')->with($network)->once();

        $this->bootApplication([new DeleteNetworkCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteNetworkCommand::NAME, ['network' => 'my-network'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Network deleted', $display);
    }
}
