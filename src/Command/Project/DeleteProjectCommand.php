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
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\ProjectConfiguration;

class DeleteProjectCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:delete';

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
            ->setAliases(['delete'])
            ->setDescription('Delete the project');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $this->projectConfiguration->validate();

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
