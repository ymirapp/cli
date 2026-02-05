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
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\SupportsMediaInterface;

class CopyMediaDirectoryStep implements BuildStepInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The build media directory where the files are copied to.
     *
     * @var string
     */
    private $mediaDirectory;

    /**
     * The project directory where the media files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, string $mediaDirectory, string $projectDirectory)
    {
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $mediaDirectory;
        $this->projectDirectory = rtrim($projectDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Copying media directory';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        $projectType = $projectConfiguration->getProjectType();

        if (!$projectType instanceof SupportsMediaInterface) {
            throw new UnsupportedProjectException('You can only use this build step with projects that support media operations');
        }

        $files = $projectType->getMediaFiles($this->projectDirectory);

        foreach ($files as $file) {
            $this->copyFile($file);
        }
    }

    /**
     * Copy an individual file or directory.
     */
    private function copyFile(SplFileInfo $file): void
    {
        $targetPath = $this->mediaDirectory.'/'.$file->getRelativePathname();

        if ($file->isDir()) {
            $this->filesystem->mkdir($targetPath);
        } elseif ($file->isFile() && 0 === $file->getSize()) {
            $this->filesystem->mkdir(dirname($targetPath));
            $this->filesystem->touch($targetPath);
        } elseif ($file->isFile() && is_string($file->getRealPath())) {
            $this->filesystem->copy($file->getRealPath(), $targetPath);
        }
    }
}
