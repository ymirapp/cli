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
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

class CopyWordPressFilesStep extends AbstractBuildStep
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
        return 'Copying WordPress files';
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
        $files = $this->getProjectFiles($projectConfiguration->getProjectType());

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
     * Get files from "include" node.
     */
    private function getIncludedFiles(array $paths): Finder
    {
        return $this->getBaseFinder()
            ->path($paths)
            ->followLinks();
    }

    /**
     * Get the Finder object for finding all the project files.
     */
    private function getProjectFiles(string $projectType): Finder
    {
        $finder = $this->getBaseFinder()
            ->notName(['ymir.yml'])
            ->followLinks();

        if (is_readable($this->projectDirectory.'/.gitignore')) {
            $finder->ignoreVCSIgnored(true);
        }

        if ('wordpress' === $projectType) {
            $finder->exclude('wp-content/uploads');

            // wp-config.php is often in .gitignore, so we need to add it back
            $finder->append([$this->getSplFileInfo('/wp-config.php')]);
        } elseif ('bedrock' === $projectType) {
            $finder->exclude('web/app/uploads');
        }

        return $finder;
    }

    /**
     * Get a SplFileInfo object for a project file.
     */
    private function getSplFileInfo(string $path): SplFileInfo
    {
        return new SplFileInfo($this->projectDirectory.$path, $this->projectDirectory, $path);
    }
}
