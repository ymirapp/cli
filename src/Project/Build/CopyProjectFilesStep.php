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
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

class CopyProjectFilesStep implements BuildStepInterface
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
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem, string $projectDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Copying Project files';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        if ($this->filesystem->exists($this->buildDirectory)) {
            $this->filesystem->remove($this->buildDirectory);
        }

        $this->filesystem->mkdir($this->buildDirectory, 0755);
        $files = $projectConfiguration->getProjectType()->getProjectFiles($this->projectDirectory);
        $includePaths = $environmentConfiguration->getBuildIncludePaths();

        if ($environmentConfiguration->isImageDeploymentType()) {
            $files->append([$this->getSplFileInfo('/.dockerignore')]);
        }

        if (!empty($includePaths)) {
            $files->append($this->getIncludedFiles($includePaths));
        }

        foreach ($files as $file) {
            $this->copyFile($file);
        }
    }

    /**
     * Copy an individual file or directory.
     */
    private function copyFile(SplFileInfo $file): void
    {
        $targetPath = $this->buildDirectory.'/'.$file->getRelativePathname();

        if ($file->isDir()) {
            $this->filesystem->mkdir($targetPath);
        } elseif ($file->isFile() && 0 === $file->getSize()) {
            $this->filesystem->touch($targetPath);
        } elseif ($file->isFile() && is_string($file->getRealPath())) {
            $this->filesystem->copy($file->getRealPath(), $targetPath);
        }
    }

    /**
     * Get base Finder object.
     */
    private function getBaseFinder(): Finder
    {
        return Finder::create()
            ->in($this->projectDirectory)
            ->files();
    }

    /**
     * Get the Finder object for finding the all the files from "build.include" configuration node.
     */
    private function getIncludedFiles(array $patterns): Finder
    {
        $patterns = collect($patterns)->map(function (string $pattern) {
            return '/'.ltrim($pattern, '/');
        });

        $files = $patterns->map(function (string $pattern): string {
            return '/'.ltrim($pattern, '/');
        })->filter(function (string $pattern): bool {
            return is_file($this->projectDirectory.$pattern);
        })->map(function (string $pattern): \SplFileInfo {
            return $this->getSplFileInfo($pattern);
        });

        $paths = $patterns->filter(function (string $pattern): bool {
            return !is_file($this->projectDirectory.$pattern);
        });

        return $this->getBaseFinder()
            ->path($paths->all())
            ->append($files->all())
            ->followLinks();
    }

    /**
     * Get a SplFileInfo object for a project file.
     */
    private function getSplFileInfo(string $path): SplFileInfo
    {
        return new SplFileInfo($this->projectDirectory.$path, $this->projectDirectory, $path);
    }
}
