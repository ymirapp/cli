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

use Illuminate\Support\Collection;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\EventListener\ProjectTeamGuardSubscriber;
use Ymir\Cli\Tests\Mock\ApiClientMockTrait;
use Ymir\Cli\Tests\Mock\CliConfigurationMockTrait;
use Ymir\Cli\Tests\Mock\CommandMockTrait;
use Ymir\Cli\Tests\Mock\InputInterfaceMockTrait;
use Ymir\Cli\Tests\Mock\OutputInterfaceMockTrait;
use Ymir\Cli\Tests\Mock\ProjectConfigurationMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\EventListener\ProjectTeamGuardSubscriber
 */
class ProjectTeamGuardSubscriberTest extends TestCase
{
    use ApiClientMockTrait;
    use CliConfigurationMockTrait;
    use CommandMockTrait;
    use InputInterfaceMockTrait;
    use OutputInterfaceMockTrait;
    use ProjectConfigurationMockTrait;

    public function testGetSubscribedEvents()
    {
        $events = ProjectTeamGuardSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame('onConsoleCommand', $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsIgnored()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $command = $this->getCommandMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(true);

        $command->expects($this->once())
                ->method('getName')
                ->willReturn('help');

        $cliConfiguration->expects($this->never())
                         ->method('getActiveTeamId');

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);
        $subscriber->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsNotInstance()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(true);

        $cliConfiguration->expects($this->never())
                         ->method('getActiveTeamId');

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);
        $subscriber->onConsoleCommand($this->getConsoleCommandEvent());
    }

    public function testOnConsoleCommandReturnsEarlyWhenProjectConfigurationDoesNotExist()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $command = $this->getCommandMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(false);

        $cliConfiguration->expects($this->never())
                         ->method('getActiveTeamId');

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);
        $subscriber->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenProjectTeamIdIsEmpty()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $command = $this->getCommandMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(true);

        $command->expects($this->once())
                ->method('getName')
                ->willReturn('some-command');

        $cliConfiguration->expects($this->once())
                         ->method('getActiveTeamId')
                         ->willReturn(42);

        $projectConfiguration->expects($this->once())
                             ->method('getProjectId')
                             ->willReturn(1);

        $apiClient->expects($this->once())
                  ->method('getProject')
                  ->with($this->identicalTo(1))
                  ->willReturn(new Collection(['provider' => ['team' => []]]));

        $apiClient->expects($this->never())
                  ->method('getTeam');

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);
        $subscriber->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenTeamIdsMatch()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $command = $this->getCommandMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(true);

        $command->expects($this->once())
                ->method('getName')
                ->willReturn('some-command');

        $cliConfiguration->expects($this->once())
                         ->method('getActiveTeamId')
                         ->willReturn(42);

        $projectConfiguration->expects($this->once())
                             ->method('getProjectId')
                             ->willReturn(1);

        $apiClient->expects($this->once())
                  ->method('getProject')
                  ->with($this->identicalTo(1))
                  ->willReturn(new Collection(['provider' => ['team' => ['id' => 42]]]));

        $apiClient->expects($this->never())
                  ->method('getTeam');

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);
        $subscriber->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandThrowsExceptionWhenTeamsDontMatch()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $command = $this->getCommandMock();

        $projectConfiguration->expects($this->once())
                             ->method('exists')
                             ->willReturn(true);

        $command->expects($this->once())
                ->method('getName')
                ->willReturn('some-command');

        $cliConfiguration->expects($this->once())
                         ->method('getActiveTeamId')
                         ->willReturn(42);

        $projectConfiguration->expects($this->once())
                             ->method('getProjectId')
                             ->willReturn(1);

        $apiClient->expects($this->once())
                  ->method('getProject')
                  ->with($this->identicalTo(1))
                  ->willReturn(new Collection(['provider' => ['team' => ['id' => 24, 'name' => 'Project Team']]]));

        $apiClient->expects($this->once())
                  ->method('getTeam')
                  ->with($this->identicalTo(42))
                  ->willReturn(new Collection(['name' => 'Active Team']));

        $subscriber = new ProjectTeamGuardSubscriber($apiClient, $cliConfiguration, $projectConfiguration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your active team "Active Team" does not match the project\'s team "Project Team". Use the "team:select 24" command to switch to the project\'s team.');

        $subscriber->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    private function getConsoleCommandEvent($command = null): ConsoleCommandEvent
    {
        $input = $this->getInputInterfaceMock();
        $output = $this->getOutputInterfaceMock();

        return new ConsoleCommandEvent($command, $input, $output);
    }
}
