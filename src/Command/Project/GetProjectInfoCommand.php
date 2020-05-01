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
use Ymir\Cli\Command\Environment\GetEnvironmentInfoCommand;
use Ymir\Cli\Console\OutputStyle;

class GetProjectInfoCommand extends AbstractProjectCommand
{
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
            ->setAliases(['info'])
            ->setDescription('Get the information on the project');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $project = $this->apiClient->getProject($this->projectConfiguration->getProjectId());

        $output->horizontalTable(
            ['Name', 'Region'],
            [[$project['name'], $project['region']]]
        );

        $this->invoke($output, GetEnvironmentInfoCommand::NAME);
    }
}
