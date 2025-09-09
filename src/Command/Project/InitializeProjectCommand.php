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

use Illuminate\Support\Collection;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\InstallableProjectTypeInterface;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\ProjectTypeRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

class InitializeProjectCommand extends AbstractCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'init';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:init';

    /**
     * The initialization step locator.
     *
     * @var ServiceLocator
     */
    private $initializationStepLocator;

    /**
     * The project types.
     *
     * @var ProjectTypeInterface[]
     */
    private $projectTypes;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, ServiceLocator $initializationStepLocator, iterable $projectTypes)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->initializationStepLocator = $initializationStepLocator;

        foreach ($projectTypes as $projectType) {
            $this->addProjectType($projectType);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mustBeInteractive(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Initialize a new project')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (
            $this->getProjectConfiguration()->exists()
            && !$this->output->confirm('A project already exists in this directory. Do you want to overwrite it?', false)
        ) {
            return;
        } elseif ($this->getProjectConfiguration()->exists()) {
            $this->getProjectConfiguration()->delete();
        }

        $projectType = $this->fulfill(new ProjectTypeRequirement($this->projectTypes, 'What is the type of the project being created?'));

        if (!$projectType instanceof ProjectTypeInterface) {
            throw new RuntimeException('Unable to determine the project type');
        }

        // Composer needs an empty directory to create a project so this has to run before we create the ymir.yml file
        if (
            $projectType instanceof InstallableProjectTypeInterface
            && $projectType->isEligibleForInstallation($this->getProjectDirectory())
            && $this->output->confirm(sprintf('%s wasn\'t detected in the project directory. Would you like to install it?', $projectType->getName()))
        ) {
            $this->output->info($projectType->getInstallationMessage());

            $projectType->installProject($this->getProjectDirectory());
        }

        $environments = $this->getBaseEnvironmentsConfiguration($projectType);

        $name = $this->fulfill(new NameSlugRequirement('What is the name of the project being created?', basename(getcwd() ?: '') ?: null));
        $provider = $this->fulfill(new CloudProviderRequirement('Which cloud provider should the project be on?'));
        $region = $this->fulfill(new RegionRequirement('Which region should the project be created in?'), ['provider' => $provider]);

        $projectRequirements = [
            'type' => $projectType,
            'name' => $name,
            'provider' => $provider,
            'region' => $region,
            'environments' => $environments->keys()->all(),
        ];

        $initializationConfigurationChanges = collect($projectType->getInitializationSteps())
            ->map(function (string $stepClass) use ($projectRequirements): ?ConfigurationChangeInterface {
                return $this->initializationStepLocator->get($stepClass)->perform($this->getContext(), $projectRequirements);
            })
            ->filter();

        $environments = $environments->map(function (EnvironmentConfiguration $configuration) use ($initializationConfigurationChanges, $projectType): EnvironmentConfiguration {
            foreach ($initializationConfigurationChanges as $initializationConfigurationChange) {
                $configuration = $initializationConfigurationChange->apply($configuration, $projectType);
            }

            return $configuration;
        });

        $project = $this->provision(Project::class, $projectRequirements);

        if (!$project instanceof Project) {
            throw new RuntimeException(sprintf('Unable to provision the "<comment>%s</comment>" project', $name));
        }

        $this->setProject($project);

        $this->getProjectConfiguration()->createNew($project, $environments, $projectType);

        $this->output->info(sprintf('Initialized <comment>%s</comment> project "<comment>%s</comment>"', $projectType->getName(), $project->getName()));
    }

    /**
     * Add a project type to the command.
     */
    private function addProjectType(ProjectTypeInterface $projectType): void
    {
        $this->projectTypes[] = $projectType;
    }

    /**
     * Get the base environments configuration for the project.
     */
    private function getBaseEnvironmentsConfiguration(ProjectTypeInterface $projectType): Collection
    {
        return collect(Project::DEFAULT_ENVIRONMENTS)->mapWithKeys(function (string $environment) use ($projectType): array {
            return [$environment => $projectType->generateEnvironmentConfiguration($environment)];
        });
    }
}
