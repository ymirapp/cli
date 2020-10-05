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

namespace Ymir\Cli\Command\Team;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

class SelectTeamCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'team:select';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Select a new currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $teams = $this->apiClient->getTeams();

        if ($teams->isEmpty()) {
            throw new RuntimeException('You\'re not on any team');
        }

        $teamId = $output->choiceWithId('Enter the ID of the team that you want to switch to', $teams);

        $this->cliConfiguration->setActiveTeamId($teamId);

        $output->infoWithValue('Your active team is now', $teams->firstWhere('id', $teamId)['name']);
    }
}
