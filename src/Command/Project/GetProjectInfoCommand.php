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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentInfoCommand;

class GetProjectInfoCommand extends AbstractCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'info';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information on the project')
            ->addArgument('project', InputArgument::OPTIONAL, 'The ID or name of the project to fetch the information of')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $projectId = $this->projectConfiguration->exists() ? $this->projectConfiguration->getProjectId() : null;

        if (null === $projectId) {
            $projectId = $this->determineProject('Which project would you like to fetch the information on');
        }

        $project = $this->apiClient->getProject($projectId);

        $this->output->horizontalTable(
            ['Name', 'Provider', 'Region'],
            [[$project['name'], $project['provider']['name'], $project['region']]]
        );

        $this->invoke(GetEnvironmentInfoCommand::NAME);
    }
}
