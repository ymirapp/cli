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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Console\ConsoleOutput;

class ChangeEnvironmentVariableCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:change';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change an environment variable')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment where the environment variable is', 'staging')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment variable')
            ->addArgument('value', InputArgument::OPTIONAL, 'The value of the environment variable');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $environment = $this->getStringArgument($input, 'environment');
        $name = $this->getStringArgument($input, 'name');
        $value = $this->getStringArgument($input, 'value');

        if (empty($name) && $input->isInteractive()) {
            $name = $output->ask('What is the name of the environment variable');
        }

        if (empty($value) && $input->isInteractive()) {
            $value = $output->ask('What is the value of the environment variable');
        }

        $this->apiClient->changeEnvironmentVariables($this->projectConfiguration->getProjectId(), $environment, [
            $name => $value,
        ]);

        $output->info('Environment variable changed');
        $output->newLine();
        $output->writeln(sprintf('<comment>Note:</comment> You need to redeploy the project to the "<comment>%s</comment>" environment using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', $environment, DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
