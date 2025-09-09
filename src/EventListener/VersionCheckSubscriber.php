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

namespace Ymir\Cli\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\GitHubClient;

class VersionCheckSubscriber implements EventSubscriberInterface
{
    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * The API client that interacts with the GitHub API.
     *
     * @var GitHubClient
     */
    private $gitHubClient;

    /**
     * The version of the Ymir console application.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     */
    public function __construct(CliConfiguration $cliConfiguration, GitHubClient $gitHubClient, string $version)
    {
        $this->cliConfiguration = $cliConfiguration;
        $this->gitHubClient = $gitHubClient;
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    /**
     * Check for a new version of the CLI before you run a command.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($this->isRunningOnContinuousIntegrationEnvironment()) {
            return;
        }

        $time = time();

        if ($time - $this->cliConfiguration->getGitHubLastCheckedTimestamp() > 3600) {
            $this->cliConfiguration->setGitHubCliVersion(ltrim($this->gitHubClient->getTags('ymirapp/cli')->pluck('name')->first(), 'v'));
            $this->cliConfiguration->setGitHubLastCheckedTimestamp($time);
        }

        $latestVersion = $this->cliConfiguration->getGitHubCliVersion();

        if (version_compare($latestVersion, $this->version, '>')) {
            $event->getOutput()->writeln(sprintf('<comment>A new version of the Ymir CLI is available:</comment> <info>%s</info> → <info>%s</info>', $this->version, $latestVersion));
        }
    }

    /**
     * Check if the CLI is running on a continuous integration environment.
     */
    private function isRunningOnContinuousIntegrationEnvironment(): bool
    {
        return getenv('GITHUB_ACTIONS')
            || getenv('TRAVIS')
            || getenv('CIRCLECI')
            || getenv('GITLAB_CI')
            || getenv('CI');
    }
}
