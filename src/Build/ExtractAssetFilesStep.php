<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Build;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExtractAssetFilesStep implements BuildStepInterface
{
    /**
     * The assets directory where the asset files are copied to.
     *
     * @var string
     */
    private $assetsDirectory;

    /**
     * The build directory where the asset files are extracted from.
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
    public function __construct(string $assetsDirectory, string $buildDirectory, Filesystem $filesystem)
    {
        $this->assetsDirectory = rtrim($assetsDirectory, '/');
        $this->buildDirectory = rtrim($buildDirectory, '/');
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
    public function perform()
    {
        if ($this->filesystem->exists($this->assetsDirectory)) {
            $this->filesystem->remove($this->assetsDirectory);
        }

        $this->filesystem->mkdir($this->buildDirectory, 0755);

        foreach ($this->getAssetFiles() as $file) {
            $this->moveAssetFile($file);
        }
    }

    /**
     * Get the asset files that we want to extract.
     */
    private function getAssetFiles(): Finder
    {
        return Finder::create()
            ->in($this->buildDirectory)
            ->files()
            ->notName(['.htaccess', '*.php'])
            ->followLinks()
            ->ignoreVcs(true)
            ->ignoreDotFiles(true);
    }

    /**
     * Move the asset file to the assets directory.
     */
    private function moveAssetFile(SplFileInfo $file)
    {
        if (!$file->isFile() || !is_string($file->getRealPath())) {
            return;
        }

        $this->filesystem->copy($file->getRealPath(), $this->assetsDirectory.'/'.$file->getRelativePathname());
    }
}
