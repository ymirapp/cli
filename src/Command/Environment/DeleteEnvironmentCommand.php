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
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Exception\InvalidInputException;

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
            ->addOption('--delete-resources', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->determineEnvironment();

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" environment?', $environment), !$this->input->isInteractive())) {
            return;
        }

        $deleteResources = $this->input->getBooleanOption('delete-resources') || $this->output->confirm('Do you want to delete all the environment resources on the cloud provider?', false);

        $this->apiClient->deleteEnvironment($this->projectConfiguration->getProjectId(), $environment, $deleteResources);

        $this->projectConfiguration->deleteEnvironment($environment);

        $message = 'Environment deleted';
        $deleteResources ? $this->output->infoWithDelayWarning($message) : $this->output->info($message);
    }

    /**
     * Determine the environment we want to delete.
     */
    private function determineEnvironment(): string
    {
        $environment = $this->input->getStringArgument('environment');
        $environments = $this->apiClient->getEnvironments($this->projectConfiguration->getProjectId())->pluck('name');

        if ($environments->isEmpty()) {
            throw new RuntimeException(sprintf('The current project doesn\'t have any environments. You can create one with the "%s" command.', CreateEnvironmentCommand::class));
        } elseif (empty($environment)) {
            $environment = $this->output->choice('Please choose an environment to delete', $environments);
        }

        if (!$environments->contains($environment)) {
            throw new InvalidInputException(sprintf('The "%s" environment doesn\'t exist.', $environment));
        }

        return $environment;
    }
}
