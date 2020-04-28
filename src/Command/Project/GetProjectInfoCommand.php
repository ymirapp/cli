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
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentInfoCommand;
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\ProjectConfiguration;

class GetProjectInfoCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:info';

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->projectConfiguration = $projectConfiguration;
    }

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
