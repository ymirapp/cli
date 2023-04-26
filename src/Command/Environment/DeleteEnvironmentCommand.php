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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class DeleteEnvironmentCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to delete')
            ->addOption('--confirm', null, InputOption::VALUE_NONE)
            ->addOption('--delete-resources', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $environment = $this->determineEnvironment($input, $output);

        $confirm = $input->getOption('confirm') ?: $output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" environment?', $environment), false);

        if (!$confirm) {
            return;
        }

        $deleteResources = $input->getOption('delete-resources') ?: $output->confirm('Do you want to delete all the environment resources on the cloud provider?', false);

        $this->apiClient->deleteEnvironment($this->projectConfiguration->getProjectId(), $environment, $deleteResources);

        $this->projectConfiguration->deleteEnvironment($environment);

        $message = 'Environment deleted';
        $deleteResources ? $output->infoWithDelayWarning($message) : $output->info($message);
    }

    /**
     * Determine the environment we want to delete.
     */
    private function determineEnvironment(InputInterface $input, OutputInterface $output): string
    {
        $environment = $this->getStringArgument($input, 'environment');
        $environments = $this->apiClient->getEnvironments($this->projectConfiguration->getProjectId())->pluck('name');

        if ($environments->isEmpty()) {
            throw new RuntimeException(sprintf('The current project doesn\'t have any environments. You can create one with the "%s" command.', CreateEnvironmentCommand::class));
        } elseif (empty($environment)) {
            $environment = $output->choice('Please choose an environment to delete', $environments);
        }

        if (!$environments->contains($environment)) {
            throw new RuntimeException(sprintf('The "%s" environment doesn\'t exist.', $environment));
        }

        return $environment;
    }
}
