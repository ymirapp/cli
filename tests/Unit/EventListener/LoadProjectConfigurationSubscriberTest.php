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
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\EventListener\LoadProjectConfigurationSubscriber;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Tests\TestCase;

class LoadProjectConfigurationSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = LoadProjectConfigurationSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame('onConsoleCommand', $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandLoadsConfiguration(): void
    {
        $input = \Mockery::mock(InputInterface::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $input->shouldReceive('getOption')->once()
              ->with('ymir-file')
              ->andReturn('ymir.yml');

        $projectConfiguration->shouldReceive('loadConfiguration')->once()
                             ->with('ymir.yml');

        (new LoadProjectConfigurationSubscriber($projectConfiguration))->onConsoleCommand($this->getConsoleCommandEvent(null, $input));
    }

    public function testOnConsoleCommandThrowsExceptionWhenYmirFileOptionIsNotString(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "--ymir-file" option must be a string value');

        $input = \Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
              ->with('ymir-file')
              ->andReturn(null);

        (new LoadProjectConfigurationSubscriber(\Mockery::mock(ProjectConfiguration::class)))->onConsoleCommand($this->getConsoleCommandEvent(null, $input));
    }

    public function testOnConsoleCommandValidatesConfigurationWhenCommandIsLocalProjectCommand(): void
    {
        $command = $this->getMockBuilder(StubLocalProjectCommand::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $input = \Mockery::mock(InputInterface::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $input->shouldReceive('getOption')->once()
              ->with('ymir-file')
              ->andReturn('ymir.yml');

        $projectConfiguration->shouldReceive('loadConfiguration')->once()
                             ->with('ymir.yml');

        $projectConfiguration->shouldReceive('validate')->once();

        (new LoadProjectConfigurationSubscriber($projectConfiguration))->onConsoleCommand($this->getConsoleCommandEvent($command, $input));
    }

    private function getConsoleCommandEvent($command = null, $input = null): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent($command, $input ?? \Mockery::mock(InputInterface::class), \Mockery::mock(OutputInterface::class));
    }
}

abstract class StubLocalProjectCommand extends Command implements LocalProjectCommandInterface
{
}
