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

namespace Ymir\Cli\Project\Type;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Initialization;
use Ymir\Cli\Support\Arr;

abstract class AbstractProjectType implements ProjectTypeInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnvironmentConfiguration(string $environment, array $baseConfiguration = []): EnvironmentConfiguration
    {
        return new EnvironmentConfiguration($environment, $this->generateEnvironmentConfigurationArray($environment, $baseConfiguration));
    }

    /**
     * {@inheritDoc}
     */
    public function getInitializationSteps(): array
    {
        return [
            Initialization\DatabaseInitializationStep::class,
            Initialization\CacheInitializationStep::class,
            Initialization\DockerInitializationStep::class,
            Initialization\IntegrationInitializationStep::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectFiles(string $projectDirectory): Finder
    {
        $finder = $this->getBaseFinder($projectDirectory)
            ->notName(['ymir.yml'])
            ->followLinks();

        if (is_readable($projectDirectory.'/.gitignore')) {
            $finder->ignoreVCSIgnored(true);
        }

        return $finder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug(): string
    {
        return strtolower($this->getName());
    }

    /**
     * Generate the environment configuration array for the given environment.
     */
    protected function generateEnvironmentConfigurationArray(string $environment, array $baseConfiguration = []): array
    {
        $configuration = array_merge([
            'architecture' => 'arm64',
            'gateway' => false,
        ], $baseConfiguration);

        if ('staging' === $environment) {
            $configuration = Arr::add($configuration, 'cron', false);
            $configuration = Arr::add($configuration, 'warmup', false);
        }

        return $configuration;
    }

    /**
     * Get a base Finder object for searching files in the given project directory.
     */
    protected function getBaseFinder(string $projectDirectory): Finder
    {
        return Finder::create()
            ->in($projectDirectory)
            ->files();
    }

    /**
     * Get the absolute or relative path to a given path.
     *
     * If the project directory is given, it will return the absolute path to the given path. Otherwise, it will
     * return the relative path.
     */
    protected function getPath(string $path, string $projectDirectory = ''): string
    {
        return !empty($projectDirectory) ? rtrim($projectDirectory, '/').'/'.$path : $path;
    }

    /**
     * Get a SplFileInfo object for a project file.
     */
    protected function getSplFileInfo(string $directory, string $path): SplFileInfo
    {
        return new SplFileInfo($directory.$path, $directory, $path);
    }

    /**
     * Check if the given paths exist relative to the given project directory.
     */
    protected function pathsExist(string $projectDirectory, array $paths): bool
    {
        return $this->filesystem->exists(array_map(function (string $path) use ($projectDirectory) {
            return $this->getPath($path, $projectDirectory);
        }, $paths));
    }
}
