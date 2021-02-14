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
use Ymir\Cli\Application;
use Ymir\Cli\GitHubClient;

class VersionCheckSubscriber implements EventSubscriberInterface
{
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
    public function __construct(GitHubClient $gitHubClient, string $version)
    {
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
        $latestVersion = ltrim($this->gitHubClient->getTags('ymirapp/cli')->pluck('name')->first(), 'v');

        if (version_compare($latestVersion, $this->version, '>')) {
            $event->getOutput()->writeln('<comment>A new version of the Ymir CLI is available</comment>');
        }
    }
}
