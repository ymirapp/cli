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

class CurrentCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'team:current';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get the name of your currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, SymfonyStyle $output)
    {
        $team = $this->apiClient->getTeam($this->getActiveTeamId());

        if (!isset($team['name'])) {
            throw new RuntimeException('Unable to get the details on your currently active team');
        }

        $team = $team->only(['id', 'name']);

        $output->writeln("<info>Your currently active team is:</info>\n");
        $output->horizontalTable($team->keys()->all(), [$team->all()]);
    }
}
