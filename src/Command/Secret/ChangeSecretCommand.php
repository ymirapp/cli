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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Console\OutputStyle;

class ChangeSecretCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'secret:change';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change an environment\'s secret')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the secret')
            ->addArgument('value', InputArgument::OPTIONAL, 'The secret value')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = (string) $this->getStringOption($input, 'environment');
        $name = $this->getStringArgument($input, 'name');
        $value = $this->getStringArgument($input, 'value');

        if (empty($name) && $input->isInteractive()) {
            $name = $output->ask('What is the name of the secret');
        }

        if (empty($value) && $input->isInteractive()) {
            $value = $output->ask('What is the secret value');
        }

        $this->apiClient->changeSecret($this->projectConfiguration->getProjectId(), $environment, $name, $value);

        $output->info('Secret changed');
        $output->newLine();
        $output->writeln(sprintf('<comment>Note:</comment> You need to redeploy the project to the "<comment>%s</comment>" environment using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', $environment, DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
