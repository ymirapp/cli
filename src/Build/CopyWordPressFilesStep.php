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
use Tightenco\Collect\Support\Arr;
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

        $environment = (array) $projectConfiguration->getEnvironment($environment);
        $files = $this->getProjectFiles($projectConfiguration->getProjectType());

        if (Arr::has($environment, 'build.include')) {
            $files->append($this->getIncludedFiles(Arr::get($environment, 'build.include')));
        }

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
            $this->filesystem->mkdir($this->buildDirectory.'/'.$file->getRelativePathname());
        } elseif ($file->isFile() && is_string($file->getRealPath())) {
            $this->filesystem->copy($file->getRealPath(), $this->buildDirectory.'/'.$file->getRelativePathname());
        }
    }

    /**
     * Get base Finder object.
     */
    private function getBaseFinder(): Finder
    {
        return Finder::create()
            ->in($this->projectDirectory)
            ->files();
    }

    /**
     * List of files we need to append manually because they're in the default Bedrock .gitignore file.
     */
    private function getBedrockFilesToAppend(): array
    {
        // Need the .env file for WP-CLI to work during the build
        $files = [new SplFileInfo($this->projectDirectory.'/.env', $this->projectDirectory, '/.env')];

        // Finder can't seem to honor the .gitignore path ignoring child folders in the mu-plugins
        // folder while keeping the files at the root of the mu-plugins folder.
        $finder = $this->getBaseFinder()
            ->path('/^web\/app\/mu-plugins/')
            ->depth('== 3');

        foreach ($finder as $file) {
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Get files from "include" node.
     */
    private function getIncludedFiles(array $paths): Finder
    {
        return $this->getBaseFinder()
            ->path($paths)
            ->followLinks();
    }

    /**
     * Get the Finder object for finding all the project files.
     */
    private function getProjectFiles(string $projectType): Finder
    {
        $finder = $this->getBaseFinder()
            ->notName(['ymir.yml'])
            ->followLinks();

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
