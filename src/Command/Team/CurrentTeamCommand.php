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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function perform(Input $input, Output $output)
    {
        $team = $this->apiClient->getTeam($this->cliConfiguration->getActiveTeamId());

        if (!isset($team['id'], $team['name'])) {
            throw new RuntimeException('Unable to get the details on your currently active team');
        }

        $user = $this->apiClient->getAuthenticatedUser();

        $output->info('Your currently active team is:');
        $output->horizontalTable(
            ['Id', 'Name', 'Owner'],
            [$team->only(['id', 'name', 'owner'])->mapWithKeys(function ($value, $key) use ($user) {
                if ('owner' == $key && $value['id'] === $user['id']) {
                    $value = 'You';
                } elseif ('owner' == $key && $value['id'] !== $user['id']) {
                    $value = $value['name'];
                }

                return [$key => $value];
            })->all()]
        );
    }
}
