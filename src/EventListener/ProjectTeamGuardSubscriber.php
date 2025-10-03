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

use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\LoginCommand;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Command\Team;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class ProjectTeamGuardSubscriber implements EventSubscriberInterface
{
    /**
     * Commands that exempted from the guard check.
     */
    private const IGNORED_COMMANDS = [
        'help',
        'list',
        LoginCommand::NAME,
        InitializeProjectCommand::NAME,
        Team\CurrentTeamCommand::NAME,
        Team\ListTeamsCommand::NAME,
        Team\SelectTeamCommand::NAME,
    ];

    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        $this->apiClient = $apiClient;
        $this->cliConfiguration = $cliConfiguration;
        $this->projectConfiguration = $projectConfiguration;
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
     * Checks that the current team matches the project team if in a project directory.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (!$this->projectConfiguration->exists() || !$this->cliConfiguration->hasActiveTeam() || !$event->getCommand() instanceof Command || in_array($event->getCommand()->getName(), self::IGNORED_COMMANDS)) {
            return;
        }

        $activeTeamId = $this->cliConfiguration->getActiveTeamId();
        $projectTeam = Arr::get($this->apiClient->getProject($this->projectConfiguration->getProjectId()), 'provider.team');

        if (empty($projectTeam['id']) || $activeTeamId === $projectTeam['id']) {
            return;
        }

        $activeTeam = $this->apiClient->getTeam($activeTeamId);

        throw new RuntimeException(sprintf('Your active team "%s" does not match the project\'s team "%s". Use the "%s %d" command to switch to the project\'s team.', $activeTeam['name'], $projectTeam['name'], Team\SelectTeamCommand::NAME, $projectTeam['id']));
    }
}
