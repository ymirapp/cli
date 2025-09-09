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

class CurrentTeamCommand extends AbstractCommand
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
            ->setDescription('Get the details on your currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $team = $this->getTeam();
        $user = $this->apiClient->getAuthenticatedUser();

        $this->output->info('Your currently active team is:');
        $this->output->horizontalTable(
            ['Id', 'Name', 'Owner'],
            [[
                $team->getId(),
                $team->getName(),
                $team->getOwner()->getId() === $user->getId() ? 'You' : $team->getOwner()->getName(),
            ]]
        );
    }
}
