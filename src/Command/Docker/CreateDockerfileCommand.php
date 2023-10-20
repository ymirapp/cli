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

namespace Ymir\Cli\Command\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\ProjectConfiguration\ImageDeploymentConfigurationChange;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class CreateDockerfileCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:create';

    /**
     * The project Dockerfile.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Dockerfile $dockerfile, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->dockerfile = $dockerfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new Dockerfile')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to create the Dockerfile for')
            ->addOption('configure-project', null, InputOption::VALUE_NONE, 'Configure project\'s ymir.yml file');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $environment = $input->getStringArgument('environment', false);
        $message = 'Dockerfile created';

        if (!empty($environment)) {
            $message .= sprintf(' for "<comment>%s</comment>" environment', $environment);
        }

        if (!$this->dockerfile->exists($environment) || $output->confirm('Dockerfile already exists. Do you want to overwrite it?', false)) {
            $this->dockerfile->create($environment);

            $output->info($message);
        }

        if (!$input->getBooleanOption('configure-project') && !$output->confirm('Would you also like to configure your project for container image deployment?')) {
            return;
        }

        $configurationChange = new ImageDeploymentConfigurationChange();

        empty($environment) ? $this->projectConfiguration->applyChangesToEnvironments($configurationChange) : $this->projectConfiguration->applyChangesToEnvironment($environment, $configurationChange);
    }
}
