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

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Team;

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
    protected function perform()
    {
        $this->output->info('You are on the following teams:');

        $user = $this->apiClient->getAuthenticatedUser();

        $this->output->table(
            ['Id', 'Name', 'Owner'],
            $this->apiClient->getTeams()->map(function (Team $team) use ($user) {
                return [
                    $team->getId(),
                    $team->getName(),
                    $team->getOwner()->getId() === $user->getId() ? 'You' : $team->getOwner()->getName(),
                ];
            })->all()
        );
    }
}
