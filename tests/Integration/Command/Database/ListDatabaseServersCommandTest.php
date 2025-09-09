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

use Ymir\Cli\Command\Database\ListDatabaseServersCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListDatabaseServersCommandTest extends TestCase
{
    public function testListDatabaseServers(): void
    {
        $team = $this->setupActiveTeam();

        $server1 = DatabaseServerFactory::create([
            'id' => 1,
            'name' => 'server1',
            'region' => 'us-east-1',
            'status' => 'available',
        ]);
        $server2 = DatabaseServerFactory::create([
            'id' => 2,
            'name' => 'server2',
            'region' => 'eu-west-1',
            'status' => 'creating',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server1, $server2]));

        $this->bootApplication([new ListDatabaseServersCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $tester = $this->executeCommand(ListDatabaseServersCommand::NAME);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('1', $display);
        $this->assertStringContainsString('server1', $display);
        $this->assertStringContainsString('us-east-1', $display);
        $this->assertStringContainsString('available', $display);

        $this->assertStringContainsString('2', $display);
        $this->assertStringContainsString('server2', $display);
        $this->assertStringContainsString('eu-west-1', $display);
        $this->assertStringContainsString('creating', $display);
    }
}
