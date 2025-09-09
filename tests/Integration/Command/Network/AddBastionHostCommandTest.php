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

use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\BastionHostFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class AddBastionHostCommandTest extends TestCase
{
    public function testAddBastionHost(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network', 'bastion_host' => null]);
        $bastionHost = BastionHostFactory::create(['id' => 1, 'private_key' => 'PRIVATE-KEY', 'status' => 'creating']);
        $availableBastionHost = BastionHostFactory::create(['id' => 1, 'private_key' => 'PRIVATE-KEY', 'status' => 'available', 'endpoint' => 'bastion.example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('addBastionHost')->with($network)->andReturn($bastionHost);
        $this->apiClient->shouldReceive('getBastionHost')->with(1)->andReturn($availableBastionHost);

        $this->filesystem->touch($this->homeDir.'/.ssh/config');

        $this->bootApplication([new AddBastionHostCommand($this->apiClient, $this->createExecutionContextFactory([
            Network::class => function () { return new NetworkDefinition(); },
        ]), $this->filesystem, $this->homeDir)]);

        $tester = $this->executeCommand(AddBastionHostCommand::NAME, ['network' => '1'], [
            'y', // Create SSH private key in ~/.ssh (which is actually $this->homeDir/.ssh)
            'y', // Configure SSH
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Bastion host added', $display);
        $this->assertStringContainsString('PRIVATE-KEY', $display);
        $this->assertStringContainsString('SSH configured', $display);

        $this->assertFileExists($this->homeDir.'/.ssh/ymir-my-network');
        $this->assertEquals('PRIVATE-KEY', file_get_contents($this->homeDir.'/.ssh/ymir-my-network'));
        $this->assertStringContainsString('Host bastion.example.com', file_get_contents($this->homeDir.'/.ssh/config'));
    }
}
