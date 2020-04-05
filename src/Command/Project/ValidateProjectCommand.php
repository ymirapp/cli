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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class ValidateProjectCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:validate';

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
            ->setAliases(['validate'])
            ->setDescription('Validates the project configuration')
            ->addArgument('environments', InputArgument::OPTIONAL, 'The environments to validate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        if (!$this->projectConfiguration->exists()) {
            throw new RuntimeException('No project configuration file found');
        }

        $environments = $input->getArgument('environments');

        if (!is_array($environments)) {
            $environments = (array) $environments;
        }

        $this->projectConfiguration->validate($environments);

        if (empty($environments)) {
            $environments = $this->projectConfiguration->getEnvironments();
        }

        $projectId = $this->projectConfiguration->getProjectId();

        foreach ($environments as $environment) {
            $this->apiClient->validateProjectConfiguration($projectId, $environment, $this->projectConfiguration);
        }

        $output->info('Project configuration is valid');
    }
}
