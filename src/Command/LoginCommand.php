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

namespace Placeholder\Cli\Command;

use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;

class LoginCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'login';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Authenticate with placeholder');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        if ($this->apiClient->isAuthenticated()
            && !$output->confirm('You are already logged in. Do you want to log in again?', false)
        ) {
            return;
        }

        $email = $output->ask('Email');
        $password = $output->askHidden('Password');

        $this->setAccessToken($this->apiClient->getAccessToken($email, $password));

        $team = $this->apiClient->getActiveTeam();

        if (isset($team['id'])) {
            $this->setActiveTeamId($team['id']);
        }

        $output->info('Logged in successfully!');
    }
}
