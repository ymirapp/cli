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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Support\Arr;

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
            ->setDescription('Select a new currently active team')
            ->addArgument('team', InputArgument::OPTIONAL, 'The ID of the team to make your currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $teams = $this->apiClient->getTeams();

        if ($teams->isEmpty()) {
            throw new RuntimeException('You\'re not on any team');
        }

        $teamId = $this->getNumericArgument($input, 'team');

        if (0 !== $teamId && !$teams->contains('id', $teamId)) {
            throw new RuntimeException(sprintf('You\'re not on a team with ID %s', $teamId));
        } elseif (0 === $teamId) {
            $user = $this->apiClient->getUser();

            $teamId = $output->choiceWithId('Enter the ID of the team that you want to switch to', $teams->map(function (array $team) use ($user) {
                $owner = (string) Arr::get($team, 'owner.name');

                if ($user['id'] === Arr::get($team, 'owner.id')) {
                    $owner = 'You';
                }

                return [
                    'id' => $team['id'],
                    'name' => sprintf('%s (<info>Owner</info>: %s)', $team['name'], $owner),
                ];
            }));
        }

        $this->cliConfiguration->setActiveTeamId($teamId);

        $output->infoWithValue('Your active team is now', $teams->firstWhere('id', $teamId)['name']);
    }
}
