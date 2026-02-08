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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

class BuildZipArchiveStep implements BuildStepInterface
{
    /**
     * The path to the build zip archive.
     *
     * @var string
     */
    private $buildArchivePath;

    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildArchivePath, string $buildDirectory)
    {
        $this->buildArchivePath = $buildArchivePath;
        $this->buildDirectory = rtrim($buildDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Building zip archive';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        $archive = new \ZipArchive();
        $archive->open($this->buildArchivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = $projectConfiguration->getProjectType()->getArchiveFiles($this->buildDirectory);
        $includePaths = $environmentConfiguration->getBuildIncludePaths();
        $totalSize = 0;

        if (!empty($includePaths)) {
            $files->append($this->getIncludedFiles($includePaths));
        }

        foreach ($files as $file) {
            $this->addFileToArchive($archive, $file);
            $totalSize += $file->getSize();
        }

        $archive->close();

        if ($totalSize >= 147005412) {
            throw new BuildFailedException(sprintf("The uncompressed build is %s bytes. It must be less than 147005412 bytes to be able to deploy. You can avoid this error by switching to container image deployment.\n\nPlease refer to this guide to learn how: https://docs.ymirapp.com/guides/container-image-deployment.html", $totalSize));
        }
    }

    /**
     * Add the given file to the given zip archive.
     */
    private function addFileToArchive(\ZipArchive $archive, SplFileInfo $file): void
    {
        if (!$file->isFile() || !is_string($file->getRealPath())) {
            return;
        }

        $relativePathName = str_replace('\\', '/', $file->getRelativePathname());
        $archive->addFile($file->getRealPath(), $relativePathName);
        $archive->setExternalAttributesName($relativePathName, \ZipArchive::OPSYS_UNIX, (33060 & 0xFFFF) << 16);
    }

    /**
     * Get files from "include" node.
     */
    private function getIncludedFiles(array $paths): Finder
    {
        return Finder::create()
            ->in($this->buildDirectory)
            ->files()
            ->path($paths);
    }
}
