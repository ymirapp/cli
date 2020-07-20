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
     * List of files we need to append manually because they're in the default Bedrock .gitignore file.
     */
    public function getBedrockFilesToAppend(): array
    {
        // Need the .env file for WP-CLI to work during the build
        $files = [new SplFileInfo($this->projectDirectory.'/.env', $this->projectDirectory, '/.env')];

        // Finder can't seem to honor the .gitignore path ignoring child folders in the mu-plugins
        // folder while keeping the files at the root of the mu-plugins folder.
        $finder = Finder::create()->in($this->projectDirectory)
            ->path('/^web\/app\/mu-plugins/')
            ->depth('== 3')
            ->files();

        foreach ($finder as $file) {
            $files[] = $file;
        }

        // TODO: Remove once we can install with Composer
        $finder = Finder::create()->in($this->projectDirectory)
            ->path('/^web\/app\/plugins\/ymir/')
            ->files();

        foreach ($finder as $file) {
            $files[] = $file;
        }

        return $files;
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

        foreach ($this->getProjectFiles($projectConfiguration->getProjectType()) as $file) {
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
    private function getProjectFiles(string $projectType): Finder
    {
        $finder = Finder::create()
            ->in($this->projectDirectory)
            ->exclude(['.idea', '.ymir'])
            ->notName(['ymir.yml'])
            ->followLinks()
            ->ignoreVcs(true)
            ->ignoreDotFiles(false);

        if (is_readable($this->projectDirectory.'/.gitignore')) {
            $finder->ignoreVCSIgnored(true);
        }

        if ('wordpress' === $projectType) {
            $finder->exclude('wp-content/uploads');
        } elseif ('bedrock' === $projectType) {
            $finder->exclude('web/app/uploads');
            $finder->append($this->getBedrockFilesToAppend());
        }

        return $finder;
    }
}
