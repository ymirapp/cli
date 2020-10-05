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
use Ymir\Cli\Console\ConsoleOutput;

class GetProjectInfoCommand extends AbstractProjectCommand
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
            ->setDescription('Get the information on the project')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $project = $this->apiClient->getProject($this->projectConfiguration->getProjectId());

        $output->horizontalTable(
            ['Name', 'Provider', 'Region'],
            [[$project['name'], $project['provider']['name'], $project['region']]]
        );

        $this->invoke($output, GetEnvironmentInfoCommand::NAME);
    }
}
