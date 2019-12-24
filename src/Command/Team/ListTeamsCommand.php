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
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;

class ListTeamsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'team:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all the teams that you\'re on');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $teams = $this->apiClient->getTeams();
        $user = $this->apiClient->getUser();

        $output->writeln("<info>You are on the following teams:</info>\n");

        $output->table(
            ['Id', 'Name', 'Owner'],
            $teams->map(function (array $team) use ($user) {
                return [
                    $team['id'],
                    $team['name'],
                    $team['owner']['id'] === $user['id'] ? 'You' : $team['owner']['name'],
                ];
            })->all()
        );
    }
}
