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

use Ymir\Cli\Command\Team\CreateTeamCommand;
use Ymir\Cli\Resource\Definition\TeamDefinition;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateTeamCommandTest extends TestCase
{
    public function testCreateTeamRePromptsIfNameIsEmpty(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'New Team',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);

        $this->setupActiveTeam();
        $this->apiClient->shouldReceive('createTeam')->with('New Team')->andReturn($team);

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new CreateTeamCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateTeamCommand::NAME, [], ['', 'New Team']);

        $this->assertStringContainsString('Team created', $tester->getDisplay());
    }

    public function testCreateTeamSuccessfullyWithArgument(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'New Team',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);

        $this->setupActiveTeam();
        $this->apiClient->shouldReceive('createTeam')->with('New Team')->andReturn($team);

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new CreateTeamCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateTeamCommand::NAME, ['name' => 'New Team']);

        $this->assertStringContainsString('Team created', $tester->getDisplay());
    }

    public function testCreateTeamSuccessfullyWithInput(): void
    {
        $team = TeamFactory::create([
            'id' => 1,
            'name' => 'New Team',
            'owner' => ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com'],
        ]);

        $this->setupActiveTeam();
        $this->apiClient->shouldReceive('createTeam')->with('New Team')->andReturn($team);

        $contextFactory = $this->createExecutionContextFactory([
            Team::class => function () { return new TeamDefinition(); },
        ]);

        $this->bootApplication([new CreateTeamCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateTeamCommand::NAME, [], ['New Team']);

        $this->assertStringContainsString('Team created', $tester->getDisplay());
    }
}
