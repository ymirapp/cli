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

namespace Ymir\Cli\Tests\Integration\Command\Team;

use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Team\ListTeamsCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\Factory\UserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListTeamsCommandTest extends TestCase
{
    public function testListTeamsEmpty(): void
    {
        $user = UserFactory::create(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com']);

        $this->apiClient->shouldReceive('getAuthenticatedUser')->andReturn($user);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection());

        $contextFactory = $this->createExecutionContextFactory();

        $command = new ListTeamsCommand($this->apiClient, $contextFactory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('You are on the following teams:', $display);
    }

    public function testListTeamsSuccessfully(): void
    {
        $user = UserFactory::create(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com']);
        $team1 = TeamFactory::create([
            'id' => 1,
            'name' => 'Team A',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);
        $team2 = TeamFactory::create([
            'id' => 2,
            'name' => 'Team B',
            'owner' => ['id' => 2, 'name' => 'Other', 'email' => 'other@example.com'],
        ]);

        $this->apiClient->shouldReceive('getAuthenticatedUser')->andReturn($user);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team1, $team2]));

        $contextFactory = $this->createExecutionContextFactory();

        $command = new ListTeamsCommand($this->apiClient, $contextFactory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('You are on the following teams:', $display);
        $this->assertStringContainsString('1', $display);

        $this->assertStringContainsString('Team A', $display);
        $this->assertStringContainsString('You', $display);

        $this->assertStringContainsString('2', $display);
        $this->assertStringContainsString('Team B', $display);

        $this->assertStringContainsString('Other', $display);
    }
}
