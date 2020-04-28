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
use Ymir\Cli\Console\OutputStyle;

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
            ->setDescription('Get the name of your currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $team = $this->apiClient->getTeam($this->getActiveTeamId());
        $user = $this->apiClient->getUser();

        if (!isset($team['id'], $team['name'])) {
            throw new RuntimeException('Unable to get the details on your currently active team');
        }

        $team = $team->only(['id', 'name', 'owner'])->mapWithKeys(function ($value, $key) use ($user) {
            if ('owner' == $key && $value['id'] === $user['id']) {
                $value = 'You';
            } elseif ('owner' == $key && $value['id'] !== $user['id']) {
                $value = $value['name'];
            }

            return [$key => $value];
        });

        $output->info('Your currently active team is:');
        $output->table(['Id', 'Name', 'Owner'], [$team->all()]);
    }
}
