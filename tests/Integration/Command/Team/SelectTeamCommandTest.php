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

use Ymir\Cli\Command\Team\SelectTeamCommand;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Resource\Definition\TeamDefinition;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class SelectTeamCommandTest extends TestCase
{
    public function testSelectTeamFailsIfNoTeamsFound(): void
    {
        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('You are not a member of any teams');

        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection());

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $this->executeCommand(SelectTeamCommand::NAME);
    }

    public function testSelectTeamFailsIfTeamNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a team with "123" as the ID or name');

        $team = TeamFactory::create(['id' => 1, 'name' => 'Team A']);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $this->executeCommand(SelectTeamCommand::NAME, ['team' => '123']);
    }

    public function testSelectTeamSuccessfullyWithArgumentId(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'Team A',
        ]);

        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(SelectTeamCommand::NAME, ['team' => '1']);

        $this->assertStringContainsString('Your active team is now: Team A', $tester->getDisplay());
        $this->assertEquals(1, $this->cliConfiguration->getActiveTeamId());
    }

    public function testSelectTeamSuccessfullyWithArgumentName(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'Team A',
        ]);

        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(SelectTeamCommand::NAME, ['team' => '1']);

        $this->assertStringContainsString('Your active team is now: Team A', $tester->getDisplay());
        $this->assertEquals(1, $this->cliConfiguration->getActiveTeamId());
    }

    public function testSelectTeamSuccessfullyWithChoice(): void
    {
        $team1 = TeamFactory::create([
            'id' => 1,
            'name' => 'Team A',
        ]);
        $team2 = TeamFactory::create([
            'id' => 2,
            'name' => 'Team B',
        ]);

        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team1, $team2]));

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(SelectTeamCommand::NAME, [], ['2']);

        $this->assertStringContainsString('Your active team is now: Team B', $tester->getDisplay());
        $this->assertEquals(2, $this->cliConfiguration->getActiveTeamId());
    }

    public function testSelectTeamWhenNoTeamIsActive(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'Team A',
        ]);

        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new SelectTeamCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(SelectTeamCommand::NAME, ['team' => '1']);

        $this->assertStringContainsString('Your active team is now: Team A', $tester->getDisplay());
        $this->assertEquals(1, $this->cliConfiguration->getActiveTeamId());
    }
}
