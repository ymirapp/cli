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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Build;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\SupportsMediaInterface;
use Ymir\Cli\Resource\Model\Environment;

class BuildProjectCommand extends AbstractCommand implements LocalProjectCommandInterface
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
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ServiceLocator $buildStepLocator, ExecutionContextFactory $contextFactory)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->buildStepLocator = $buildStepLocator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Build the project for an environment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to build')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Run the build in debug mode')
            ->addOption('with-media', null, InputOption::VALUE_NONE, 'Copy the media directory during the build');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $projectType = $this->getProjectConfiguration()->getProjectType();
        $withMediaOption = $this->input->getBooleanOption('with-media');

        if ($withMediaOption && !$projectType instanceof SupportsMediaInterface) {
            throw new UnsupportedProjectException('This project type doesn\'t support media operations');
        }

        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to build?');
        $environmentConfiguration = $this->getProjectConfiguration()->getEnvironmentConfiguration($environment->getName());

        $this->output->info(sprintf('Building <comment>%s</comment> project for the <comment>%s</comment> environment', $this->getProject()->getName(), $environment->getName()));

        if ($withMediaOption) {
            $this->performBuildStep($this->buildStepLocator->get(Build\CopyMediaDirectoryStep::class), $environmentConfiguration);
        }

        collect($this->getProjectConfiguration()->getProjectType()->getBuildSteps())->map(function (string $buildStep) {
            return $this->buildStepLocator->get($buildStep);
        })->each(function (Build\BuildStepInterface $buildStep) use ($environmentConfiguration): void {
            $this->performBuildStep($buildStep, $environmentConfiguration);
        });

        if ($this->input->getBooleanOption('debug')) {
            $this->performBuildStep($this->buildStepLocator->get(Build\DebugBuildStep::class), $environmentConfiguration);
        }

        switch ($environmentConfiguration->getDeploymentType()) {
            case 'image':
                $this->performBuildStep($this->buildStepLocator->get(Build\BuildContainerImageStep::class), $environmentConfiguration);

                break;
            default:
                $this->performBuildStep($this->buildStepLocator->get(Build\CompressBuildFilesStep::class), $environmentConfiguration);

                break;
        }

        $this->output->info('Project built successfully');
    }

    /**
     * Perform a build step.
     */
    private function performBuildStep(Build\BuildStepInterface $buildStep, EnvironmentConfiguration $environmentConfiguration): void
    {
        $this->output->writeStep($buildStep->getDescription());

        $buildStep->perform($environmentConfiguration, $this->getProjectConfiguration());
    }
}
