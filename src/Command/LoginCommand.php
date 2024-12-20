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

namespace Ymir\Cli\Command;

use Ymir\Sdk\Exception\ClientException;

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
            ->setDescription('Authenticate with Ymir API');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if ($this->apiClient->isAuthenticated()
            && !$this->output->confirm('You are already logged in. Do you want to log in again?', false)
        ) {
            return;
        }

        $email = $this->output->ask('Email');
        $password = $this->output->askHidden('Password');

        try {
            $accessToken = $this->apiClient->getAccessToken($email, $password);
        } catch (ClientException $exception) {
            if (!$exception->getValidationErrors()->has('authentication_code')) {
                throw $exception;
            }

            $accessToken = $this->apiClient->getAccessToken($email, $password, $this->output->askHidden('Authentication code'));
        }

        $this->apiClient->setAccessToken($accessToken);
        $this->cliConfiguration->setAccessToken($accessToken);

        $team = $this->apiClient->getActiveTeam();

        if (isset($team['id'])) {
            $this->cliConfiguration->setActiveTeamId($team['id']);
        }

        $this->output->info('Logged in successfully');
    }
}
