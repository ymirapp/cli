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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;

class CopyUploadsDirectoryStep implements BuildStepInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The project directory where the uploads files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * The build "uploads" directory where the files are copied to.
     *
     * @var string
     */
    private $uploadsDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, string $projectDirectory, string $uploadsDirectory)
    {
        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Copying "uploads" directory';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $projectType = $projectConfiguration->getProjectType();

        if (!$projectType instanceof AbstractWordPressProjectType) {
            throw new RuntimeException('You can only use this build step with WordPress projects');
        }

        $files = Finder::create()->files()->in($projectType->getUploadsDirectoryPath($this->projectDirectory));

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
            $this->filesystem->mkdir($this->uploadsDirectory.'/'.$file->getRelativePathname());
        } elseif ($file->isFile() && is_string($file->getRealPath())) {
            $this->filesystem->copy($file->getRealPath(), $this->uploadsDirectory.'/'.$file->getRelativePathname());
        }
    }
}
