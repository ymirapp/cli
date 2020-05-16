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
use Ymir\Cli\ProjectConfiguration;

class CopyWordPressFilesStep implements BuildStepInterface
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
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, ProjectConfiguration $projectConfiguration, string $projectDirectory, Filesystem $filesystem)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->projectConfiguration = $projectConfiguration;
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
    public function perform(string $environment)
    {
        if ($this->filesystem->exists($this->buildDirectory)) {
            $this->filesystem->remove($this->buildDirectory);
        }

        $this->filesystem->mkdir($this->buildDirectory, 0755);

        foreach ($this->getProjectFiles() as $file) {
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
     * Get the Finder object for finding all the project files.
     */
    private function getProjectFiles(): Finder
    {
        $finder = Finder::create()
            ->in($this->projectDirectory)
            ->exclude(['.idea', '.ymir'])
            ->notName(['ymir.yml'])
            ->followLinks()
            ->ignoreVcs(true)
            ->ignoreDotFiles(false);
        $projectType = $this->projectConfiguration->getProjectType();

        if (is_readable($this->projectDirectory.'/.gitignore')) {
            $finder->ignoreVCSIgnored(true);
        }

        if ('wordpress' === $projectType) {
            $finder->exclude('wp-content/uploads');
        }

        return $finder;
    }
}
