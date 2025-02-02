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
    public function isNeeded(array $buildOptions, ProjectConfiguration $projectConfiguration): bool
    {
        return !empty($buildOptions['uploads']);
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        switch ($projectConfiguration->getProjectType()) {
            case 'bedrock':
                $projectUploadsDirectory = $this->projectDirectory.'/web/app/uploads';

                break;
            case 'radicle':
                $projectUploadsDirectory = $this->projectDirectory.'/public/content/uploads';

                break;
            default:
                $projectUploadsDirectory = $this->projectDirectory.'/wp-content/uploads';

                break;
        }

        $files = Finder::create()->files()->in($projectUploadsDirectory);

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
