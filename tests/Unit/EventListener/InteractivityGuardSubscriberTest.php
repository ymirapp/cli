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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\EventListener\InteractivityGuardSubscriber;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Tests\TestCase;

class InteractivityGuardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = InteractivityGuardSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame(['onConsoleCommand', 10], $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandDoesNotThrowExceptionWhenCommandMustBeInteractiveAndInputIsInteractive(): void
    {
        $command = \Mockery::mock(AbstractCommand::class);
        $command->shouldReceive('mustBeInteractive')->andReturn(true);

        $input = \Mockery::mock(InputInterface::class);
        $input->shouldReceive('isInteractive')->once()
              ->andReturn(true);

        (new InteractivityGuardSubscriber())->onConsoleCommand($this->getConsoleCommandEvent($command, $input));
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsNotAbstractCommand(): void
    {
        $command = \Mockery::mock(Command::class);

        (new InteractivityGuardSubscriber())->onConsoleCommand($this->getConsoleCommandEvent($command));

        $this->addToAssertionCount(1);
    }

    public function testOnConsoleCommandThrowsExceptionWhenCommandMustBeInteractiveAndInputIsNotInteractive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot run "foo" command in non-interactive mode');

        $command = \Mockery::mock(AbstractCommand::class);
        $command->shouldReceive('getName')->andReturn('foo');
        $command->shouldReceive('mustBeInteractive')->andReturn(true);

        $input = \Mockery::mock(InputInterface::class);
        $input->shouldReceive('isInteractive')->once()
              ->andReturn(false);

        (new InteractivityGuardSubscriber())->onConsoleCommand($this->getConsoleCommandEvent($command, $input));
    }

    private function getConsoleCommandEvent($command = null, $input = null): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent($command, $input ?? \Mockery::mock(InputInterface::class), \Mockery::mock(OutputInterface::class));
    }
}
