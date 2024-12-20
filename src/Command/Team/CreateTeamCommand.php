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
use Ymir\Cli\Command\AbstractCommand;

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
    protected function perform()
    {
        $name = $this->input->getStringArgument('name');

        if (empty($name)) {
            $name = (string) $this->output->ask('What is the name of the team');
        }

        $this->apiClient->createTeam($name);

        $this->output->info('Team created');
    }
}
