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
     * The path to the build artifact.
     *
     * @var string
     */
    private $buildFilePath;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, string $buildFilePath)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->buildFilePath = $buildFilePath;
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
    public function perform(string $environment)
    {
        $archive = new \ZipArchive();
        $archive->open($this->buildFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($this->getBuildFiles() as $file) {
            $this->addFileToArchive($archive, $file);
        }

        $archive->close();

        $size = round(filesize($this->buildFilePath) / 1048576, 1);

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
