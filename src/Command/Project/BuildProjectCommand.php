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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Build\BuildContainerImageStep;
use Ymir\Cli\Build\BuildStepInterface;
use Ymir\Cli\Build\CompressBuildFilesStep;
use Ymir\Cli\Build\CopyUploadsDirectoryStep;
use Ymir\Cli\Build\DebugBuildStep;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class BuildProjectCommand extends AbstractProjectCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'build';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:build';

    /**
     * Service locator with all the build steps.
     *
     * @var ServiceLocator
     */
    private $buildStepLocator;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApiClient $apiClient, ServiceLocator $buildStepLocator, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->buildStepLocator = $buildStepLocator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Build the project for deployment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to build', 'staging')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Run the build in debug mode')
            ->addOption('with-uploads', null, InputOption::VALUE_NONE, 'Copy the "uploads" directory during the build');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->info('Building project');

        if ($this->input->getBooleanOption('with-uploads')) {
            $this->performBuildStep($this->buildStepLocator->get(CopyUploadsDirectoryStep::class));
        }

        collect($this->projectConfiguration->getProjectType()->getBuildSteps())->map(function (string $buildStep) {
            return $this->buildStepLocator->get($buildStep);
        })->each(function (BuildStepInterface $buildStep) {
            $this->performBuildStep($buildStep);
        });

        if ($this->input->getBooleanOption('debug')) {
            $this->performBuildStep($this->buildStepLocator->get(DebugBuildStep::class));
        }

        switch ($this->projectConfiguration->getEnvironmentDeploymentType($this->input->getStringArgument('environment'))) {
            case 'image':
                $this->performBuildStep($this->buildStepLocator->get(BuildContainerImageStep::class));

                break;
            default:
                $this->performBuildStep($this->buildStepLocator->get(CompressBuildFilesStep::class));

                break;
        }

        $this->output->info('Project built successfully');
    }

    /**
     * Perform a build step.
     */
    private function performBuildStep(BuildStepInterface $buildStep)
    {
        $this->output->writeStep($buildStep->getDescription());
        $buildStep->perform($this->input->getStringArgument('environment'), $this->projectConfiguration);
    }
}
