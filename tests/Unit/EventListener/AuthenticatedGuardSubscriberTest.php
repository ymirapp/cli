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
use Ymir\Cli\ApiClient;
use Ymir\Cli\EventListener\AuthenticatedGuardSubscriber;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Tests\TestCase;

class AuthenticatedGuardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = AuthenticatedGuardSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame(['onConsoleCommand', PHP_INT_MAX], $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandDoesNotThrowExceptionWhenAuthenticated(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $command = \Mockery::mock(Command::class);

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $apiClient->shouldReceive('isAuthenticated')->once()
                  ->andReturn(true);

        (new AuthenticatedGuardSubscriber($apiClient))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsIgnored(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $command = \Mockery::mock(Command::class);

        $command->shouldReceive('getName')->once()
                ->andReturn('help');

        $apiClient->shouldReceive('isAuthenticated')->never();

        (new AuthenticatedGuardSubscriber($apiClient))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    public function testOnConsoleCommandReturnsEarlyWhenCommandIsNotInstance(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);

        $apiClient->shouldReceive('isAuthenticated')->never();

        (new AuthenticatedGuardSubscriber($apiClient))->onConsoleCommand($this->getConsoleCommandEvent());
    }

    public function testOnConsoleCommandThrowsExceptionWhenNotAuthenticated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please authenticate using the "login" command before using this command');

        $apiClient = \Mockery::mock(ApiClient::class);
        $command = \Mockery::mock(Command::class);

        $command->shouldReceive('getName')->once()
                ->andReturn('some-command');

        $apiClient->shouldReceive('isAuthenticated')->once()
                  ->andReturn(false);

        (new AuthenticatedGuardSubscriber($apiClient))->onConsoleCommand($this->getConsoleCommandEvent($command));
    }

    private function getConsoleCommandEvent($command = null): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent($command, \Mockery::mock(InputInterface::class), \Mockery::mock(OutputInterface::class));
    }
}
