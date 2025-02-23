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

namespace Ymir\Cli\Build;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

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
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        if ($this->filesystem->exists($this->buildDirectory)) {
            $this->filesystem->remove($this->buildDirectory);
        }

        $this->filesystem->mkdir($this->buildDirectory, 0755);

        $environment = $projectConfiguration->getEnvironment($environment);
        $files = $projectConfiguration->getProjectType()->getProjectFiles($this->projectDirectory);

        if ('image' === Arr::get($environment, 'deployment')) {
            $files->append([$this->getSplFileInfo('/.dockerignore')]);
        }

        if (Arr::has($environment, 'build.include')) {
            $files->append($this->getIncludedFiles(Arr::get($environment, 'build.include')));
        }

        foreach ($files as $file) {
            $this->copyFile($file);
        }
    }

    /**
     * Copy an individual file or directory.
     */
    private function copyFile(SplFileInfo $file)
    {
        if ($file->isDir()) {
            $this->filesystem->mkdir($this->buildDirectory.'/'.$file->getRelativePathname());
        } elseif ($file->isFile() && is_string($file->getRealPath())) {
            $this->filesystem->copy($file->getRealPath(), $this->buildDirectory.'/'.$file->getRelativePathname());
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

        $files = $patterns->map(function (string $pattern) {
            return '/'.ltrim($pattern, '/');
        })->filter(function (string $pattern) {
            return is_file($this->projectDirectory.$pattern);
        })->map(function (string $pattern) {
            return $this->getSplFileInfo($pattern);
        });

        $paths = $patterns->filter(function (string $pattern) {
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
