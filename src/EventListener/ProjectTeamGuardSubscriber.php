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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\Command\LoginCommand;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Command\Team as TeamCommand;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Project\ProjectLocator;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Team\TeamLocator;

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
        TeamCommand\CurrentTeamCommand::NAME,
        TeamCommand\ListTeamsCommand::NAME,
        TeamCommand\SelectTeamCommand::NAME,
    ];

    /**
     * The Ymir project locator.
     *
     * @var ProjectLocator
     */
    private $projectLocator;

    /**
     * The Ymir team locator.
     *
     * @var TeamLocator
     */
    private $teamLocator;

    /**
     * Constructor.
     */
    public function __construct(ProjectLocator $projectLocator, TeamLocator $teamLocator)
    {
        $this->projectLocator = $projectLocator;
        $this->teamLocator = $teamLocator;
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
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof Command || in_array($command->getName(), self::IGNORED_COMMANDS)) {
            return;
        }

        $activeTeam = $this->teamLocator->getTeam();
        $project = $this->projectLocator->getProject();

        if (!$project instanceof Project || !$activeTeam instanceof Team) {
            return;
        }

        $projectTeam = $project->getTeam();

        if ($activeTeam->getId() !== $projectTeam->getId()) {
            throw new RuntimeException(sprintf('Your active team "%s" doesn\'t match the project\'s team "%s", but you can use the "%s %d" command to switch to the project\'s team', $activeTeam->getName(), $projectTeam->getName(), TeamCommand\SelectTeamCommand::NAME, $projectTeam->getId()));
        }
    }
}
