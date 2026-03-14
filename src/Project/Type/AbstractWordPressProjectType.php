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
use Ymir\Cli\Project\Initialization\WordPressConfigurationInitializationStep;

abstract class AbstractWordPressProjectType extends AbstractProjectType implements SupportsMediaInterface
{
    /**
     * {@inheritdoc}
     */
    public function getArchiveFiles(string $directory): Finder
    {
        return Finder::create()
            ->append($this->getRequiredFiles($directory))
            ->append($this->getRequiredPluginFiles($directory))
            ->append($this->getRequiredThemeFiles($directory))
            ->append($this->getRequiredFileTypes($directory))
            ->append($this->getWordPressCoreFiles($directory));
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(string $directory): Finder
    {
        return parent::getAssetFiles($directory)
            ->notName(['*.mo', '*.po']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPhpVersion(): string
    {
        return '7.4';
    }

    /**
     * {@inheritDoc}
     */
    public function getInitializationSteps(): array
    {
        $steps = parent::getInitializationSteps();

        $steps[] = WordPressConfigurationInitializationStep::class;

        return $steps;
    }

    /**
     * {@inheritDoc}
     */
    public function getMediaDirectoryName(): string
    {
        return 'uploads';
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaDirectoryPath(string $directory = ''): string
    {
        $uploadsDirectory = $this->getUploadsDirectory();

        return empty($directory) ? $uploadsDirectory : rtrim($directory, '/').$uploadsDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFiles(string $directory): Finder
    {
        return Finder::create()
            ->files()
            ->in($this->getMediaDirectoryPath($directory));
    }

    /**
     * Get the path to the "mu-plugins" directory.
     *
     * If the project directory is given, it will return the absolute path to the "mu-plugins" directory. Otherwise, it
     * will return the relative path.
     */
    public function getMustUsePluginsDirectoryPath(string $directory = ''): string
    {
        $mustUsePluginsDirectory = $this->getMustUsePluginsDirectory();

        return empty($directory) ? $mustUsePluginsDirectory : rtrim($directory, '/').$mustUsePluginsDirectory;
    }

    /**
     * Get the path to the "plugins" directory.
     *
     * If the project directory is given, it will return the absolute path to the "plugins" directory. Otherwise, it
     * will return the relative path.
     */
    public function getPluginsDirectoryPath(string $directory = ''): string
    {
        $pluginsDirectory = $this->getPluginsDirectory();

        return empty($directory) ? $pluginsDirectory : rtrim($directory, '/').$pluginsDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectFiles(string $directory): Finder
    {
        return parent::getProjectFiles($directory)
            ->exclude(ltrim($this->getMediaDirectoryPath(), '/'));
    }

    /**
     * Get the Finder object for finding all the required files in the given project directory.
     */
    protected function getRequiredFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
            ->path([
                '/^wp-cli\.yml/',
            ]);
    }

    /**
     * Get the Finder object for finding all the required file types in the given project directory.
     */
    protected function getRequiredFileTypes(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
            ->name(['*.mo', '*.php']);
    }

    /**
     * Get the Finder object for finding all the required plugin files in the given project directory.
     */
    protected function getRequiredPluginFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
            ->path([
                '/plugins\/[^\/]*\/block\.json$/',
            ]);
    }

    /**
     * Get the Finder object for finding all the required theme files in the given project directory.
     */
    protected function getRequiredThemeFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
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
    protected function getWordPressCoreFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
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
