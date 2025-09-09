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
use Ymir\Cli\Command\Team\CurrentTeamCommand;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\Factory\UserFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CurrentTeamCommandTest extends TestCase
{
    public function testCurrentTeamFailsIfNoTeamIsActive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You do not have a currently active team, but you can select a team using the "team:select" command');

        $command = new CurrentTeamCommand($this->apiClient, $this->createExecutionContextFactory());
        $tester = new CommandTester($command);

        $tester->execute([]);
    }

    public function testCurrentTeamSuccessfully(): void
    {
        $user = UserFactory::create(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com']);
        $team = TeamFactory::create([
            'id' => 123,
            'name' => 'Active Team',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);

        $this->cliConfiguration->setActiveTeamId(123);

        $this->apiClient->shouldReceive('getTeam')->with(123)->andReturn($team);
        $this->apiClient->shouldReceive('getAuthenticatedUser')->andReturn($user);

        $contextFactory = $this->createExecutionContextFactory();

        $command = new CurrentTeamCommand($this->apiClient, $contextFactory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Your currently active team is:', $display);
        $this->assertStringContainsString('123', $display);

        $this->assertStringContainsString('Active Team', $display);
        $this->assertStringContainsString('You', $display);
    }

    public function testCurrentTeamSuccessfullyWhenNotOwner(): void
    {
        $user = UserFactory::create(['id' => 2, 'name' => 'Member', 'email' => 'member@example.com']);
        $team = TeamFactory::create([
            'id' => 123,
            'name' => 'Active Team',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);

        $this->cliConfiguration->setActiveTeamId(123);

        $this->apiClient->shouldReceive('getTeam')->with(123)->andReturn($team);
        $this->apiClient->shouldReceive('getAuthenticatedUser')->andReturn($user);

        $contextFactory = $this->createExecutionContextFactory();

        $command = new CurrentTeamCommand($this->apiClient, $contextFactory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Your currently active team is:', $display);
        $this->assertStringContainsString('123', $display);

        $this->assertStringContainsString('Active Team', $display);
        $this->assertStringContainsString('Owner', $display);
    }
}
