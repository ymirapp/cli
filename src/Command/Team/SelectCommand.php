<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Team;

use Placeholder\Cli\Command\AbstractCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SelectCommand extends AbstractCommand
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
            ->setDescription('Select to a new currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, SymfonyStyle $output)
    {
        $teams = $this->apiClient->getTeams();

        if (empty($teams)) {
            throw new RuntimeException('You\'re not on any team');
        }

        $teamId = (int) $output->choice(
            'Which team would you like to switch to?',
            $teams->sortBy->name->mapWithKeys(function (array $team) {
                return [$team['id'] => $team['name']];
            })->all()
        );

        $this->setActiveTeamId($teamId);

        $output->writeln('New active team selected');
    }
}
