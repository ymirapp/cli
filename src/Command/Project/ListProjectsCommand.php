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

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class ListProjectsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the projects that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $projects = $this->apiClient->getProjects($this->cliConfiguration->getActiveTeamId());

        $output->table(
            ['Id', 'Name', 'Provider', 'Region'],
            $projects->map(function (array $project) {
                return [$project['id'], $project['name'], $project['provider']['name'], $project['region']];
            })->all()
        );
    }
}
