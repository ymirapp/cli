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

namespace Ymir\Cli\Command\Secret;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputStyle;

class DeleteSecretCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'secret:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an existing environment\'s secret')
            ->addArgument('secret', InputArgument::OPTIONAL, 'The ID or name of the secret')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = (string) $this->getStringOption($input, 'environment');
        $idOrName = $this->getStringArgument($input, 'secret');
        $secrets = $this->apiClient->getSecrets($this->projectConfiguration->getProjectId(), $environment);

        if ($secrets->isEmpty()) {
            throw new RuntimeException(sprintf('The "%s" environment has no secrets', $environment));
        }

        $secret = is_numeric($idOrName) ? $secrets->firstWhere('id', $idOrName) : $secrets->firstWhere('name', $idOrName);

        if (!is_array($secret) || empty($secret['id'])) {
            throw new InvalidArgumentException(sprintf('Unable to find a secret with the ID or name "%s"', $idOrName));
        } elseif (!$output->confirm('Are you sure you want to delete this secret?', false)) {
            return;
        }

        $this->apiClient->deleteSecret($secret['id']);

        $output->info('Secret deleted');
    }
}
