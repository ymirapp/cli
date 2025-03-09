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
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class ExtractAssetFilesStep implements BuildStepInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The build directory where the asset files are extracted from.
     *
     * @var string
     */
    private $fromDirectory;

    /**
     * The assets directory where the asset files are copied to.
     *
     * @var string
     */
    private $toDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $assetsDirectory, string $buildDirectory, Filesystem $filesystem)
    {
        $this->toDirectory = rtrim($assetsDirectory, '/');
        $this->fromDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Extracting asset files';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        if ($this->filesystem->exists($this->toDirectory)) {
            $this->filesystem->remove($this->toDirectory);
        }

        $this->filesystem->mkdir($this->toDirectory, 0755);

        foreach ($projectConfiguration->getProjectType()->getAssetFiles($this->fromDirectory) as $file) {
            $this->moveAssetFile($file);
        }
    }

    /**
     * Move the asset file to the assets directory.
     */
    private function moveAssetFile(SplFileInfo $file)
    {
        if (!$file->isFile() || !is_string($file->getRealPath())) {
            return;
        }

        $this->filesystem->copy($file->getRealPath(), $this->toDirectory.'/'.$file->getRelativePathname());
    }
}
