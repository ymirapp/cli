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

namespace Ymir\Cli\Command\Project;

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\ConsoleOutput;

class DeleteProjectCommand extends AbstractProjectCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'delete';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete the project')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        if (!$output->confirm('Are you sure you want to delete this project?', false)) {
            return;
        }

        $deleteResources = (bool) $output->confirm('Do you want to delete all the project resources on the cloud provider?', false);

        $this->apiClient->deleteProject($this->projectConfiguration->getProjectId(), $deleteResources);

        $this->projectConfiguration->delete();

        $message = 'Project deleted';
        $deleteResources ? $output->infoWithDelayWarning($message) : $output->info($message);
    }
}
