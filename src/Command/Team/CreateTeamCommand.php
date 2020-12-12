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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

class CreateTeamCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'team:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new team')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $this->retryApi(function () use ($input, $output) {
            $name = $this->getStringArgument($input, 'name');

            if (empty($name) && $input->isInteractive()) {
                $name = (string) $output->ask('What is the name of the team');
            }

            $this->apiClient->createTeam($name);
        }, 'Do you want to try creating a team again?', $output);

        $output->info('Team created');
    }
}
