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
use Ymir\Cli\Command\RendersEnvironmentInfoTrait;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class GetProjectInfoCommand extends AbstractCommand
{
    use RendersEnvironmentInfoTrait;

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
        $project = $this->resolve(Project::class, 'Which project would you like to get information about?');

        $this->output->horizontalTable(
            ['Name', 'Provider', 'Region'],
            [[$project->getName(), $project->getProvider()->getName(), $project->getRegion()]]
        );

        $this->apiClient->getEnvironments($project)->each(function (Environment $environment): void {
            $this->displayEnvironmentTable($environment);
        });
    }
}
