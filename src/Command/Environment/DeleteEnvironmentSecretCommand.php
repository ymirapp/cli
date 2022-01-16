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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class DeleteEnvironmentSecretCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:secret:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an environment\'s secret')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment where the secret is', 'staging')
            ->addArgument('secret', InputArgument::OPTIONAL, 'The ID or name of the secret');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $environment = $this->getStringArgument($input, 'environment');
        $secrets = $this->apiClient->getSecrets($this->projectConfiguration->getProjectId(), $environment);

        if ($secrets->isEmpty()) {
            throw new RuntimeException(sprintf('The "%s" environment has no secrets', $environment));
        }

        $secretIdOrName = $this->getStringArgument($input, 'secret');

        if (empty($secretIdOrName)) {
            $secretIdOrName = (string) $output->choice('Which secret would you like to delete', $secrets->pluck('name'));
        }

        $secret = is_numeric($secretIdOrName) ? $secrets->firstWhere('id', $secretIdOrName) : $secrets->firstWhere('name', $secretIdOrName);

        if (!is_array($secret) || empty($secret['id'])) {
            throw new InvalidArgumentException(sprintf('Unable to find a secret with "%s" as the ID or name', $secretIdOrName));
        } elseif (!$output->confirm('Are you sure you want to delete this secret?', false)) {
            return;
        }

        $this->apiClient->deleteSecret($secret['id']);

        $output->info('Secret deleted');
    }
}
