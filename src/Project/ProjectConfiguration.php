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

namespace Ymir\Cli\Project;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Exception\ConfigurationException;
use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Support\Arr;

class ProjectConfiguration implements Arrayable
{
    /**
     * The parsed configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * The path to the Ymir project configuration file.
     *
     * @var string
     */
    private $configurationFilePath;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * All supported project types.
     *
     * @var ProjectTypeInterface[]
     */
    private $projectTypes;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, iterable $projectTypes, string $configurationFilePath = '')
    {
        $this->filesystem = $filesystem;

        $this->loadConfiguration($configurationFilePath);

        foreach ($projectTypes as $projectType) {
            $this->addProjectType($projectType);
        }
    }

    /**
     * Add a new environment node to the project configuration.
     */
    public function addEnvironment(EnvironmentConfiguration $configuration): void
    {
        $this->configuration['environments'][$configuration->getName()] = $configuration->toArray();

        $this->save();
    }

    /**
     * Apply the given configuration changes to the given environment.
     */
    public function applyChangesToEnvironment(string $environment, ConfigurationChangeInterface $configurationChange): void
    {
        $this->configuration['environments'][$environment] = $configurationChange->apply($this->getEnvironment($environment), $this->getProjectType())->toArray();

        $this->save();
    }

    /**
     * Apply the given configuration changes to all project environments.
     */
    public function applyChangesToEnvironments(ConfigurationChangeInterface $configurationChange): void
    {
        $this->getEnvironments()->keys()->each(function (string $environment) use ($configurationChange): void {
            $this->applyChangesToEnvironment($environment, $configurationChange);
        });
    }

    /**
     * Creates a new configuration from the given project.
     *
     * Overwrites the existing project configuration.
     */
    public function createNew(Project $project, Collection $environments, ProjectTypeInterface $type): void
    {
        $this->configuration = [
            'id' => $project->getId(),
            'name' => $project->getName(),
        ];
        $this->configuration['type'] = $type->getSlug();
        $this->configuration['environments'] = $environments->map(function (EnvironmentConfiguration $configuration): array {
            return $configuration->toArray();
        })->all();

        $this->save();
    }

    /**
     * Delete the project configuration.
     */
    public function delete(): void
    {
        if ($this->exists()) {
            $this->filesystem->remove($this->configurationFilePath);
        }

        $this->configuration = [];
    }

    /**
     * Delete the given project environment.
     */
    public function deleteEnvironment(string $environment): void
    {
        if ($this->hasEnvironment($environment)) {
            unset($this->configuration['environments'][$environment]);

            $this->save();
        }
    }

    /**
     * Checks if the Ymir project configuration file exists.
     */
    public function exists(): bool
    {
        return !empty($this->configurationFilePath) && $this->filesystem->exists($this->configurationFilePath);
    }

    /**
     * Get the configuration information for the given environment.
     */
    public function getEnvironment(string $environment): EnvironmentConfiguration
    {
        if (!$this->hasEnvironment($environment)) {
            throw new InvalidArgumentException(sprintf('Environment "%s" not found in Ymir project configuration file', $environment));
        }

        return new EnvironmentConfiguration($environment, (array) $this->configuration['environments'][$environment]);
    }

    /**
     * Get the environments in the project configuration.
     */
    public function getEnvironments(): Collection
    {
        return collect($this->configuration['environments'])->mapWithKeys(function (array $configuration, string $environment) {
            return [$environment => new EnvironmentConfiguration($environment, $configuration)];
        });
    }

    /**
     * Get the project ID.
     */
    public function getProjectId(): int
    {
        if (empty($this->configuration['id'])) {
            throw new ConfigurationException('No "id" found in Ymir project configuration file');
        }

        return (int) $this->configuration['id'];
    }

    /**
     * Get the project name.
     */
    public function getProjectName(): string
    {
        if (empty($this->configuration['name'])) {
            throw new ConfigurationException('No "name" found in Ymir project configuration file');
        }

        return (string) $this->configuration['name'];
    }

    /**
     * Get the project type.
     */
    public function getProjectType(): ProjectTypeInterface
    {
        if (empty($this->configuration['type'])) {
            throw new ConfigurationException('No "type" found in Ymir project configuration file');
        }

        $projectType = $this->findProjectType($this->configuration['type']);

        if (!$projectType instanceof ProjectTypeInterface) {
            throw new UnsupportedProjectException(sprintf('Project type "%s" is not supported', $this->configuration['type']));
        }

        return $projectType;
    }

    /**
     * Check if the project configuration file has configuration information for the given environment.
     */
    public function hasEnvironment(string $environment): bool
    {
        return array_key_exists($environment, $this->configuration['environments']);
    }

    /**
     * Load the given Ymir project configuration file.
     */
    public function loadConfiguration(string $configurationFilePath): void
    {
        $configuration = [];

        if ($this->filesystem->exists($configurationFilePath)) {
            try {
                $configuration = Yaml::parse((string) file_get_contents($configurationFilePath));
            } catch (\Throwable $exception) {
                throw new ConfigurationException(sprintf('Error parsing Ymir project configuration file: %s', $exception->getMessage()));
            }
        }

        if (!empty($configuration) && !is_array($configuration)) {
            throw new ConfigurationException('Error parsing Ymir project configuration file');
        }

        $this->configuration = $configuration;
        $this->configurationFilePath = $configurationFilePath;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->configuration;
    }

    /**
     * Validates the loaded configuration file.
     */
    public function validate(): void
    {
        if (!$this->exists()) {
            throw new ConfigurationException(sprintf('No Ymir project configuration file found, but you can create one by initializing a project with the "%s" command', InitializeProjectCommand::ALIAS));
        } elseif (empty($this->configuration['id'])) {
            throw new ConfigurationException('The Ymir project configuration file must have an "id"');
        } elseif (empty($this->configuration['environments'])) {
            throw new ConfigurationException('The Ymir project configuration file must have at least one environment');
        } elseif (empty($this->configuration['type'])) {
            throw new ConfigurationException('The Ymir project configuration file must have a "type"');
        } elseif (!$this->findProjectType($this->configuration['type'])) {
            throw new UnsupportedProjectException(sprintf('The "%s" project type is not supported', $this->configuration['type']));
        }
    }

    /**
     * Add a project type to the command.
     */
    private function addProjectType(ProjectTypeInterface $projectType): void
    {
        $this->projectTypes[] = $projectType;
    }

    /**
     * Find the project type that matches the given slug.
     */
    private function findProjectType(string $slug): ?ProjectTypeInterface
    {
        return Arr::first($this->projectTypes, function (ProjectTypeInterface $projectType) use ($slug) {
            return $slug === $projectType->getSlug();
        });
    }

    /**
     * Save the configuration options to the configuration file.
     */
    private function save(): void
    {
        if (empty($this->configurationFilePath)) {
            throw new ConfigurationException('No Ymir project configuration file path set');
        }

        $this->filesystem->dumpFile($this->configurationFilePath, str_replace('!!float 8', '8.0', Yaml::dump($this->configuration, 20, 2, Yaml::DUMP_NULL_AS_TILDE)));
    }
}
