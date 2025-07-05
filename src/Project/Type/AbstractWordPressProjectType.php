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

use Symfony\Component\Finder\Finder;
use Ymir\Cli\Support\Arr;

abstract class AbstractWordPressProjectType extends AbstractProjectType
{
    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->notName(['*.php', '*.mo', '*.po'])
            ->followLinks()
            ->ignoreDotFiles(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildFiles(string $projectDirectory): Finder
    {
        return Finder::create()
            ->append($this->getRequiredFiles($projectDirectory))
            ->append($this->getRequiredPluginFiles($projectDirectory))
            ->append($this->getRequiredThemeFiles($projectDirectory))
            ->append($this->getRequiredFileTypes($projectDirectory))
            ->append($this->getWordPressCoreFiles($projectDirectory));
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentConfiguration(string $environment, array $baseConfiguration = []): array
    {
        $configuration = parent::getEnvironmentConfiguration($environment, $baseConfiguration);

        if ('staging' === $environment) {
            $configuration = Arr::add($configuration, 'cdn.caching', 'assets');
        }

        return $configuration;
    }

    /**
     * Get the path to the "mu-plugins" directory.
     *
     * If the project directory is given, it will return the absolute path to the "mu-plugins" directory. Otherwise, it
     * will return the relative path.
     */
    public function getMustUsePluginsDirectoryPath(string $projectDirectory = ''): string
    {
        return $this->getPath($this->getMustUsePluginsDirectory(), $projectDirectory);
    }

    /**
     * Get the path to the "plugins" directory.
     *
     * If the project directory is given, it will return the absolute path to the "plugins" directory. Otherwise, it
     * will return the relative path.
     */
    public function getPluginsDirectoryPath(string $projectDirectory = ''): string
    {
        return $this->getPath($this->getPluginsDirectory(), $projectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectFiles(string $projectDirectory): Finder
    {
        return parent::getProjectFiles($projectDirectory)
            ->exclude(ltrim($this->getUploadsDirectoryPath(), '/'));
    }

    /**
     * Get the path to the "uploads" directory.
     *
     * If the project directory is given, it will return the absolute path to the "uploads" directory. Otherwise, it
     * will return the relative path.
     */
    public function getUploadsDirectoryPath(string $projectDirectory = ''): string
    {
        return $this->getPath($this->getUploadsDirectory(), $projectDirectory);
    }

    /**
     * Get the Finder object for finding all the required files in the given project directory.
     */
    protected function getRequiredFiles(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->path([
                '/^wp-cli\.yml/',
            ]);
    }

    /**
     * Get the Finder object for finding all the required file types in the given project directory.
     */
    protected function getRequiredFileTypes(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->name(['*.mo', '*.php']);
    }

    /**
     * Get the Finder object for finding all the required plugin files in the given project directory.
     */
    protected function getRequiredPluginFiles(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->path([
                '/plugins\/[^\/]*\/block\.json$/',
            ]);
    }

    /**
     * Get the Finder object for finding all the required theme files in the given project directory.
     */
    protected function getRequiredThemeFiles(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->path([
                '/themes\/[^\/]*\/screenshot\.(gif|jpe?g|png)$/',
                '/themes\/[^\/]*\/style\.css$/',
                '/themes\/[^\/]*\/block\.json$/',
                '/themes\/[^\/]*\/theme\.json$/',
                '/themes\/[^\/]*\/[^\/]*\/.*\.html/',
                '/themes\/[^\/]*\/[^\/]*\/.*\.json$/',
            ]);
    }

    /**
     * Get the Finder object for finding all the WordPress core files in the given project directory.
     */
    protected function getWordPressCoreFiles(string $projectDirectory): Finder
    {
        return $this->getBaseFinder($projectDirectory)
            ->path(collect(['wp-includes\/', 'wp-admin\/'])->map(function (string $path) {
                return $this->buildWordPressCorePathPattern($path);
            })->add('/^bin\//')->all());
    }

    /**
     * Builds a regex pattern for a WordPress core path.
     */
    abstract protected function buildWordPressCorePathPattern(string $path): string;

    /**
     * Get the "mu-plugins" directory used by the project type.
     */
    abstract protected function getMustUsePluginsDirectory(): string;

    /**
     * Get the "plugins" directory used by the project type.
     */
    abstract protected function getPluginsDirectory(): string;

    /**
     * Get the "uploads" directory used by the project type.
     */
    abstract protected function getUploadsDirectory(): string;
}
