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
use Ymir\Cli\Console\ConsoleOutput;

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
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $name = $this->getStringArgument($input, 'name');

        if (!empty($name) && !$output->confirm('Are you sure you want to delete this environment?', false)) {
            return;
        } elseif (empty($name)) {
            $name = $output->choice('Please choose an environment to delete', $this->apiClient->getEnvironments($this->projectConfiguration->getProjectId())->pluck(['name'])->all());
        }

        $deleteResources = (bool) $output->confirm('Do you want to delete all the environment resources on the cloud provider?', false);

        $this->apiClient->deleteEnvironment($this->projectConfiguration->getProjectId(), $name, $deleteResources);

        $this->projectConfiguration->deleteEnvironment($name);

        $message = 'Environment deleted';
        $deleteResources ? $output->infoWithDelayWarning($message) : $output->info($message);
    }
}
