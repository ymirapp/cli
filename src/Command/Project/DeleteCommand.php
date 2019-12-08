<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Project;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

class DeleteCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:delete';

    /**
     * The placeholder project configuration.
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
        if (!$this->projectConfiguration->exists()) {
            throw new RuntimeException('No project configuration file found');
        } elseif (!$output->confirm('Are you sure you want to delete this project?', false)) {
            return;
        }

        $this->projectConfiguration->validate();

        $this->apiClient->deleteProject($this->projectConfiguration->getProjectId());

        $output->writeln(sprintf('"<info>%s</info>" project deletion has begun (This takes several several minutes)', $this->projectConfiguration->getProjectName()));

        $this->projectConfiguration->delete();
    }
}
