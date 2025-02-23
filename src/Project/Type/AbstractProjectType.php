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
    public function getEnvironmentConfiguration(string $environment, array $baseConfiguration = []): array
    {
        $configuration = array_merge([
            'architecture' => 'arm64',
        ], $baseConfiguration);

        if ('staging' === $environment) {
            $configuration = Arr::add($configuration, 'cron', false);
            $configuration = Arr::add($configuration, 'warmup', false);
        }

        return $configuration;
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
     * Get a base Finder object for searching files in the given project directory.
     */
    protected function getBaseFinder(string $projectDirectory): Finder
    {
        return Finder::create()
            ->in($projectDirectory)
            ->files();
    }

    /**
     * Get a SplFileInfo object for a project file.
     */
    protected function getSplFileInfo(string $directory, string $path): SplFileInfo
    {
        return new SplFileInfo($directory.$path, $directory, $path);
    }

    /**
     * Check if the given paths exist in the given directory.
     */
    protected function pathsExist(string $directory, array $paths): bool
    {
        return $this->filesystem->exists(array_map(function (string $path) use ($directory) {
            return $directory.$path;
        }, $paths));
    }
}
