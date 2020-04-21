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

namespace Placeholder\Cli;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Tightenco\Collect\Contracts\Support\Arrayable;
use Tightenco\Collect\Support\Collection;

class ProjectConfiguration implements Arrayable
{
    /**
     * The path to the configuration file.
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
     * The parsed configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * Constructor.
     */
    public function __construct(string $configurationFilePath, Filesystem $filesystem)
    {
        $this->configurationFilePath = $configurationFilePath;
        $this->filesystem = $filesystem;
        $this->configuration = $this->load($configurationFilePath);
    }

    /**
     * Save the options back to the configuration file when we're destroying the object.
     */
    public function __destruct()
    {
        if (empty($this->configuration)) {
            return;
        }

        $this->filesystem->dumpFile($this->configurationFilePath, Yaml::dump($this->configuration, 20, 2));
    }

    /**
     * Creates a new configuration from the given project.
     *
     * Overwrites the existing project configuration.
     */
    public function createNew(Collection $project)
    {
        $this->configuration = $project->only(['id', 'name', 'type'])->all();

        $this->configuration['environments'] = [
            'production' => null,
            'staging' => null,
        ];
    }

    /**
     * Delete the project configuration.
     */
    public function delete()
    {
        $this->configuration = [];
        $this->filesystem->remove($this->configurationFilePath);
    }

    /**
     * Checks if the project configuration file exists.
     */
    public function exists(): bool
    {
        return $this->filesystem->exists($this->configurationFilePath);
    }

    /**
     * Get the configuration information for the given environment.
     */
    public function getEnvironment(string $environment): ?array
    {
        if (!array_key_exists($environment, $this->configuration['environments'])) {
            throw new \InvalidArgumentException(sprintf('No "%s" environment found', $environment));
        }

        return $this->configuration['environments'][$environment];
    }

    /**
     * Get the environments in the project configuration.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->configuration['environments']);
    }

    /**
     * Get the project ID.
     */
    public function getProjectId(): int
    {
        if (empty($this->configuration['id'])) {
            throw new RuntimeException('Project configuration file has no "id"');
        }

        return (int) $this->configuration['id'];
    }

    /**
     * Get the project name.
     */
    public function getProjectName(): string
    {
        if (empty($this->configuration['name'])) {
            throw new RuntimeException('Project configuration file has no "name"');
        }

        return (string) $this->configuration['name'];
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
    public function validate(array $environments = null)
    {
        if (empty($this->configuration['id'])) {
            throw new RuntimeException('The project configuration file must have an "id"');
        } elseif (empty($this->configuration['environments'])) {
            throw new RuntimeException('The project configuration file must have at least one environment');
        }

        if (empty($environments)) {
            return;
        }

        $missingEnvironment = collect($environments)->diff(array_keys($this->configuration['environments']))->first();

        if (empty($missingEnvironment)) {
            return;
        }

        throw new RuntimeException(sprintf('The "%s" environment isn\'t configured in your project configuration', $missingEnvironment));
    }

    /**
     * Load the options from the configuration file.
     */
    private function load(string $configurationFilePath): array
    {
        $configuration = [];

        if ($this->filesystem->exists($configurationFilePath)) {
            $configuration = Yaml::parse((string) file_get_contents($configurationFilePath));
        }

        if (!empty($configuration) && !is_array($configuration)) {
            throw new RuntimeException('Error parsing project configuration file');
        }

        return $configuration;
    }
}
