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

namespace Ymir\Cli\Tests\Unit\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\EventListener\ProjectTeamGuardSubscriber;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Project\ProjectLocator;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Team\TeamLocator;
use Ymir\Cli\Tests\TestCase;

class ProjectTeamGuardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ProjectTeamGuardSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame('onConsoleCommand', $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsIgnored(): void
    {
        $projectLocator = \Mockery::mock(ProjectLocator::class);
        $teamLocator = \Mockery::mock(TeamLocator::class);
        $command = \Mockery::mock(Command::class);

        $projectLocator->shouldNotReceive('getProject');
        $teamLocator->shouldNotReceive('getTeam');

        $command->shouldReceive('getName')->once()
                ->andReturn('help');

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsNotInstance(): void
    {
        $projectLocator = \Mockery::mock(ProjectLocator::class);
        $teamLocator = \Mockery::mock(TeamLocator::class);

        $projectLocator->shouldNotReceive('getProject');
        $teamLocator->shouldNotReceive('getTeam');

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent());
    }

    public function testOnConsoleCommandReturnsEarlyWhenNoActiveTeam(): void
    {
        $projectLocator = \Mockery::mock(ProjectLocator::class);
        $teamLocator = \Mockery::mock(TeamLocator::class);
        $command = \Mockery::mock(Command::class);

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $teamLocator->shouldReceive('getTeam')->once()
                    ->andReturn(null);

        $projectLocator->shouldReceive('getProject')->once()
                       ->andReturn($this->getProject());

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenProjectConfigurationDoesNotExist(): void
    {
        $projectLocator = \Mockery::mock(ProjectLocator::class);
        $teamLocator = \Mockery::mock(TeamLocator::class);
        $command = \Mockery::mock(Command::class);
        $team = Team::fromArray(['id' => 42, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]);

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $teamLocator->shouldReceive('getTeam')->once()
                    ->andReturn($team);

        $projectLocator->shouldReceive('getProject')->once()
                       ->andReturn(null);

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenTeamIdsMatch(): void
    {
        $projectLocator = \Mockery::mock(ProjectLocator::class)->shouldIgnoreMissing();
        $teamLocator = \Mockery::mock(TeamLocator::class)->shouldIgnoreMissing();
        $command = \Mockery::mock(Command::class);
        $team = Team::fromArray(['id' => 42, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]);

        $projectLocator->shouldReceive('getProject')->once()
                       ->andReturn($this->getProject(['id' => 1, 'name' => 'foo', 'region' => 'us-east-1', 'provider' => ['id' => 1, 'name' => 'aws', 'team' => ['id' => 42, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]]]));

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $teamLocator->shouldReceive('getTeam')->once()
                    ->andReturn($team);

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandThrowsExceptionWhenTeamsDontMatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your active team "Active Team" doesn\'t match the project\'s team "Project Team", but you can use the "team:select 24" command to switch to the project\'s team');

        $projectLocator = \Mockery::mock(ProjectLocator::class)->shouldIgnoreMissing();
        $teamLocator = \Mockery::mock(TeamLocator::class)->shouldIgnoreMissing();
        $command = \Mockery::mock(Command::class);

        $projectLocator->shouldReceive('getProject')->once()
                       ->andReturn($this->getProject(['id' => 1, 'name' => 'foo', 'region' => 'us-east-1', 'provider' => ['id' => 1, 'name' => 'aws', 'team' => ['id' => 24, 'name' => 'Project Team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]]]));

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $teamLocator->shouldReceive('getTeam')->once()
                    ->andReturn(Team::fromArray(['id' => 42, 'name' => 'Active Team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]));

        (new ProjectTeamGuardSubscriber($projectLocator, $teamLocator))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    private function getConsoleCommandEvent($command = null): ConsoleCommandEvent
    {
        $input = \Mockery::mock(InputInterface::class);
        $output = \Mockery::mock(OutputInterface::class);

        return new ConsoleCommandEvent($command, $input, $output);
    }

    private function getProject(array $data = []): Project
    {
        if (empty($data)) {
            $data = ['id' => 1, 'name' => 'foo', 'region' => 'us-east-1', 'provider' => ['id' => 1, 'name' => 'aws', 'team' => ['id' => 1, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]]];
        }

        return Project::fromArray($data);
    }
}
