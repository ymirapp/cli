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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CompressBuildFilesStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Compressing build files';
    }

    /**
     * {@inheritdoc}
     */
    public function perform()
    {
        $archive = new \ZipArchive();
        $archive->open($this->getArchivePath(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($this->getBuildFiles() as $file) {
            $this->addFileToArchive($archive, $file);
        }

        $archive->close();

        $size = round(filesize($this->getArchivePath()) / 1048576, 1);

        if ($size > 45) {
            throw new RuntimeException(sprintf('Compressed build is greater than 45MB. Build is %sMB', $size));
        }
    }

    /**
     * Add the given file to the given zip archive.
     */
    private function addFileToArchive(\ZipArchive $archive, SplFileInfo $file)
    {
        if (!$file->isFile() || !is_string($file->getRealPath())) {
            return;
        }

        $relativePathName = str_replace('\\', '/', $file->getRelativePathname());
        $archive->addFile($file->getRealPath(), $relativePathName);
        $archive->setExternalAttributesName($relativePathName, \ZipArchive::OPSYS_UNIX, (33060 & 0xffff) << 16);
    }

    /**
     * Get the path to the archive file.
     */
    private function getArchivePath(): string
    {
        return $this->buildDirectory.'/build.zip';
    }

    /**
     * Get the Finder object for finding all the project files.
     */
    private function getBuildFiles(): Finder
    {
        return Finder::create()
            ->in($this->buildDirectory)
            ->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false);
    }
}
