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

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

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
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $output->info('You are on the following teams:');

        $user = $this->apiClient->getUser();

        $output->table(
            ['Id', 'Name', 'Owner'],
            $this->apiClient->getTeams()->map(function (array $team) use ($user) {
                return [
                    $team['id'],
                    $team['name'],
                    $team['owner']['id'] === $user['id'] ? 'You' : $team['owner']['name'],
                ];
            })->all()
        );
    }
}
