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

namespace Ymir\Cli\Project\Configuration;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
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
     * Save the options back to the configuration file when we're destroying the object.
     */
    public function __destruct()
    {
        if (empty($this->configuration)) {
            return;
        }

        $this->save();
    }

    /**
     * Add a new environment node to the project configuration.
     */
    public function addEnvironment(string $environment, array $configuration)
    {
        $this->configuration['environments'][$environment] = $configuration;
    }

    /**
     * Apply the given configuration changes to the given environment.
     */
    public function applyChangesToEnvironment(string $environment, ConfigurationChangeInterface $configurationChange)
    {
        $this->configuration['environments'][$environment] = $configurationChange->apply($this->getEnvironment($environment), $this->getProjectType());
    }

    /**
     * Apply the given configuration changes to all project environments.
     */
    public function applyChangesToEnvironments(ConfigurationChangeInterface $configurationChange)
    {
        $this->getEnvironments()->keys()->each(function (string $environment) use ($configurationChange) {
            $this->applyChangesToEnvironment($environment, $configurationChange);
        });
    }

    /**
     * Creates a new configuration from the given project.
     *
     * Overwrites the existing project configuration.
     */
    public function createNew(Collection $project, array $environments, ProjectTypeInterface $type)
    {
        $this->configuration = $project->only(['id', 'name'])->all();
        $this->configuration['type'] = $type->getSlug();
        $this->configuration['environments'] = $environments;

        $this->save();
    }

    /**
     * Delete the project configuration.
     */
    public function delete()
    {
        if ($this->exists()) {
            $this->filesystem->remove($this->configurationFilePath);
        }

        $this->configuration = [];
        $this->configurationFilePath = '';
    }

    /**
     * Delete the given project environment.
     */
    public function deleteEnvironment(string $environment)
    {
        if ($this->hasEnvironment($environment)) {
            unset($this->configuration['environments'][$environment]);
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
    public function getEnvironment(string $environment): array
    {
        if (!$this->hasEnvironment($environment)) {
            throw new InvalidArgumentException(sprintf('Environment "%s" not found in Ymir project configuration file', $environment));
        }

        return (array) $this->configuration['environments'][$environment];
    }

    /**
     * Get the environments in the project configuration.
     */
    public function getEnvironments(): Collection
    {
        return collect($this->configuration['environments']);
    }

    /**
     * Get the project ID.
     */
    public function getProjectId(): int
    {
        if (empty($this->configuration['id'])) {
            throw new RuntimeException('No "id" found in Ymir project configuration file');
        }

        return (int) $this->configuration['id'];
    }

    /**
     * Get the project name.
     */
    public function getProjectName(): string
    {
        if (empty($this->configuration['name'])) {
            throw new RuntimeException('No "name" found in Ymir project configuration file');
        }

        return (string) $this->configuration['name'];
    }

    /**
     * Get the project type.
     */
    public function getProjectType(): ProjectTypeInterface
    {
        if (empty($this->configuration['type'])) {
            throw new RuntimeException('No "type" found in Ymir project configuration file');
        }

        $projectType = $this->findProjectType($this->configuration['type']);

        if (!$projectType instanceof ProjectTypeInterface) {
            throw new RuntimeException(sprintf('Project type "%s" is not supported', $this->configuration['type']));
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
    public function loadConfiguration(string $configurationFilePath)
    {
        $configuration = [];

        if ($this->filesystem->exists($configurationFilePath)) {
            $configuration = Yaml::parse((string) file_get_contents($configurationFilePath));
        }

        if (!empty($configuration) && !is_array($configuration)) {
            throw new RuntimeException('Error parsing Ymir project configuration file');
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
    public function validate()
    {
        if (!$this->exists()) {
            throw new RuntimeException(sprintf('No Ymir project configuration file found. You can create one by initializing a project with the "%s" command.', InitializeProjectCommand::ALIAS));
        } elseif (empty($this->configuration['id'])) {
            throw new RuntimeException('The Ymir project configuration file must have an "id"');
        } elseif (empty($this->configuration['environments'])) {
            throw new RuntimeException('The Ymir project configuration file must have at least one environment');
        } elseif (empty($this->configuration['type'])) {
            throw new RuntimeException('The Ymir project configuration file must have a "type"');
        } elseif (!$this->findProjectType($this->configuration['type'])) {
            throw new RuntimeException(sprintf('The "%s" project type is not supported', $this->configuration['type']));
        }
    }

    /**
     * Add a project type to the command.
     */
    private function addProjectType(ProjectTypeInterface $projectType)
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
    private function save()
    {
        if (empty($this->configurationFilePath)) {
            throw new RuntimeException('No Ymir project configuration file path set');
        }

        $this->filesystem->dumpFile($this->configurationFilePath, str_replace('!!float 8', '8.0', Yaml::dump($this->configuration, 20, 2, Yaml::DUMP_NULL_AS_TILDE)));
    }
}
