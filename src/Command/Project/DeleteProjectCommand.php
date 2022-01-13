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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputInterface;

class DeleteProjectCommand extends AbstractCommand
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
            ->setDescription('Delete a project')
            ->addArgument('project', InputArgument::OPTIONAL, 'The ID or name of the project to delete')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $projectId = $this->projectConfiguration->exists() ? $this->projectConfiguration->getProjectId() : null;

        if (null === $projectId) {
            $projectId = $this->determineProject('Which project would you like to delete', $input, $output);
        }

        $project = $this->apiClient->getProject($projectId);

        if (!$output->confirm(sprintf('Are you sure you want to delete the <comment>%s</comment> project?', $project['name']), false)) {
            return;
        }

        $deleteResources = (bool) $output->confirm('Do you want to delete all the project resources on the cloud provider?', false);

        $this->apiClient->deleteProject($projectId, $deleteResources);

        if ($this->projectConfiguration->exists() && $projectId === $this->projectConfiguration->getProjectId()) {
            $this->projectConfiguration->delete();
        }

        $message = 'Project deleted';
        $deleteResources ? $output->infoWithDelayWarning($message) : $output->info($message);
    }
}
