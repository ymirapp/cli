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

namespace Ymir\Cli\Project\Build;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

class CleanupBuildStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Cleaning up build';
    }

    /**
     * {@inheritDoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        $filesToDelete = $projectConfiguration->getProjectType()->getExcludedFiles($this->buildDirectory)
            ->append($this->getConfiguredExcludedFiles($environmentConfiguration))
            ->notPath($environmentConfiguration->getBuildIncludePaths());

        $this->filesystem->remove($filesToDelete);
    }

    private function getConfiguredExcludedFiles(EnvironmentConfiguration $environmentConfiguration): iterable
    {
        $excludePaths = $environmentConfiguration->getBuildExcludePaths();

        if (empty($excludePaths)) {
            return [];
        }

        return Finder::create()
            ->in($this->buildDirectory)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->path($excludePaths)
            ->notPath($environmentConfiguration->getBuildIncludePaths());
    }
}
