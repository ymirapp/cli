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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\EventListener\VersionCheckSubscriber;
use Ymir\Cli\GitHubClient;
use Ymir\Cli\Tests\TestCase;

class VersionCheckSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('GITHUB_ACTIONS');
        putenv('TRAVIS');
        putenv('CIRCLECI');
        putenv('GITLAB_CI');
        putenv('CI');
    }

    public function testGetSubscribedEvents(): void
    {
        $events = VersionCheckSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertSame('onConsoleCommand', $events[ConsoleEvents::COMMAND]);
    }

    public function testOnConsoleCommandChecksGitHubWhenLastCheckedIsOld(): void
    {
        $cliConfiguration = \Mockery::mock(CliConfiguration::class);
        $gitHubClient = \Mockery::mock(GitHubClient::class);

        $cliConfiguration->shouldReceive('getGitHubLastCheckedTimestamp')->once()
                         ->andReturn(0);

        $gitHubClient->shouldReceive('getTags')->once()
                     ->with('ymirapp/cli')
                     ->andReturn(new Collection([['name' => 'v1.1.0']]));

        $cliConfiguration->shouldReceive('setGitHubCliVersion')->once()
                         ->with('1.1.0');

        $cliConfiguration->shouldReceive('setGitHubLastCheckedTimestamp')->once();

        $cliConfiguration->shouldReceive('getGitHubCliVersion')->once()
                         ->andReturn('1.1.0');

        $output = \Mockery::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->once()
               ->with($this->stringContains('A new version of the Ymir CLI is available'));

        (new VersionCheckSubscriber($cliConfiguration, $gitHubClient, '1.0.0'))->onConsoleCommand($this->getConsoleCommandEvent(null, null, $output));
    }

    public function testOnConsoleCommandDoesNotCheckGitHubWhenLastCheckedIsRecent(): void
    {
        $cliConfiguration = \Mockery::mock(CliConfiguration::class);
        $gitHubClient = \Mockery::mock(GitHubClient::class);

        $cliConfiguration->shouldReceive('getGitHubLastCheckedTimestamp')->once()
                         ->andReturn(time());

        $gitHubClient->shouldReceive('getTags')->never();

        $cliConfiguration->shouldReceive('getGitHubCliVersion')->once()
                         ->andReturn('1.0.0');

        $output = \Mockery::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->never();

        (new VersionCheckSubscriber($cliConfiguration, $gitHubClient, '1.0.0'))->onConsoleCommand($this->getConsoleCommandEvent(null, null, $output));
    }

    public function testOnConsoleCommandDoesNothingWhenRunningOnCI(): void
    {
        putenv('GITHUB_ACTIONS=true');

        $cliConfiguration = \Mockery::mock(CliConfiguration::class);
        $gitHubClient = \Mockery::mock(GitHubClient::class);

        $cliConfiguration->shouldReceive('getGitHubLastCheckedTimestamp')->never();

        (new VersionCheckSubscriber($cliConfiguration, $gitHubClient, '1.0.0'))->onConsoleCommand($this->getConsoleCommandEvent());

        putenv('GITHUB_ACTIONS');
    }

    private function getConsoleCommandEvent($command = null, $input = null, $output = null): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent($command, $input ?? \Mockery::mock(InputInterface::class), $output ?? \Mockery::mock(OutputInterface::class));
    }
}
