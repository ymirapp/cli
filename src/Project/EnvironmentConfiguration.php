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

use Ymir\Cli\Support\Arr;

class EnvironmentConfiguration
{
    /**
     * The environment configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * The name of the environment.
     *
     * @var string
     */
    private $name;

    /**
     * Constructor.
     */
    public function __construct(string $name, array $configuration = [])
    {
        $this->configuration = $configuration;
        $this->name = $name;
    }

    /**
     * Get the architecture for the environment.
     */
    public function getArchitecture(): string
    {
        return Arr::get($this->configuration, 'architecture', '');
    }

    /**
     * Get the build commands for the environment.
     */
    public function getBuildCommands(): array
    {
        $commands = [];

        if (Arr::has($this->configuration, 'build.commands')) {
            $commands = (array) Arr::get($this->configuration, 'build.commands');
        } elseif (!Arr::has($this->configuration, 'build.include') && isset($this->configuration['build'])) {
            $commands = (array) $this->configuration['build'];
        }

        return $commands;
    }

    /**
     * Get the build include paths for the environment.
     */
    public function getBuildIncludePaths(): array
    {
        return (array) Arr::get($this->configuration, 'build.include', []);
    }

    /**
     * Get the console timeout for the environment.
     */
    public function getConsoleTimeout(): int
    {
        return (int) Arr::get($this->configuration, 'console.timeout', 60);
    }

    /**
     * Get the database server name for the environment.
     */
    public function getDatabaseServerName(): ?string
    {
        $database = $this->configuration['database'] ?? null;

        return is_array($database) ? ($database['server'] ?? null) : $database;
    }

    /**
     * Get the deployment type for the environment.
     */
    public function getDeploymentType(): ?string
    {
        $deployment = $this->configuration['deployment'] ?? null;

        if (!empty($deployment['type']) && is_string($deployment['type'])) {
            $deployment = $deployment['type'];
        }

        return $deployment;
    }

    /**
     * Get the domains for the environment.
     */
    public function getDomains(): array
    {
        return (array) ($this->configuration['domain'] ?? []);
    }

    /**
     * Get the name of the environment.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the environment has build configuration.
     */
    public function hasBuildConfiguration(): bool
    {
        return !empty($this->configuration['build']);
    }

    /**
     * Check if the environment has database configuration.
     */
    public function hasDatabaseConfiguration(): bool
    {
        return !empty($this->configuration['database']);
    }

    /**
     * Check if the environment has domain configuration.
     */
    public function hasDomainConfiguration(): bool
    {
        return isset($this->configuration['domain']);
    }

    /**
     * Check if the environment is deployed using a container image.
     */
    public function isImageDeploymentType(): bool
    {
        return 'image' === $this->getDeploymentType();
    }

    /**
     * Create a new configuration instance with the given data merged in recursively.
     *
     * All resulting arrays will have unique values and be sorted recursively.
     */
    public function merge(array $configuration): self
    {
        return new self($this->name, Arr::sortRecursive(Arr::uniqueRecursive(array_merge_recursive($this->configuration, $configuration))));
    }

    /**
     * Get the environment configuration as an array.
     */
    public function toArray(): array
    {
        return $this->configuration;
    }

    /**
     * Create a new configuration instance with the given configuration merged in.
     */
    public function with(array $configuration): self
    {
        return new self($this->name, array_merge($this->configuration, $configuration));
    }

    /**
     * Create a new configuration instance with the given keys removed.
     */
    public function without(string ...$keys): self
    {
        return new self($this->name, array_diff_key($this->configuration, array_flip($keys)));
    }
}
