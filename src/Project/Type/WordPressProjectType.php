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

namespace Ymir\Cli\Project\Type;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Build;
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\GitHubClient;
use Ymir\Cli\Support\Arr;

class WordPressProjectType extends AbstractWordPressProjectType implements InstallableProjectTypeInterface
{
    /**
     * The API client that interacts with the GitHub API.
     *
     * @var GitHubClient
     */
    private $gitHubClient;
    /**
     * The WP-CLI executable.
     *
     * @var WpCliExecutable
     */
    private $wpCliExecutable;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, GitHubClient $gitHubClient, WpCliExecutable $wpCliExecutable)
    {
        parent::__construct($filesystem);

        $this->gitHubClient = $gitHubClient;
        $this->wpCliExecutable = $wpCliExecutable;
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildSteps(): array
    {
        return [
            Build\CopyProjectFilesStep::class,
            Build\DownloadWpCliStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\CopyMustUsePluginStep::class,
            Build\ModifyWordPressConfigurationStep::class,
            Build\ExtractAssetFilesStep::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallationMessage(): string
    {
        return 'Downloading WordPress using WP-CLI';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'WordPress';
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectFiles(string $projectDirectory): Finder
    {
        $projectFiles = parent::getProjectFiles($projectDirectory);

        // wp-config.php is often in .gitignore, so we force it back if the file exists
        if ($this->filesystem->exists($projectDirectory.'/wp-config.php')) {
            $projectFiles->append([$this->getSplFileInfo($projectDirectory, '/wp-config.php')]);
        }

        return $projectFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function installIntegration(string $projectDirectory)
    {
        $pluginsDirectory = $projectDirectory.'/wp-content/plugins';

        $this->gitHubClient->downloadLatestVersion('ymirapp/wordpress-plugin')->extractTo($pluginsDirectory);

        $files = Finder::create()
            ->directories()
            ->in($pluginsDirectory)
            ->path('/^ymirapp-wordpress-plugin-/')
            ->depth('== 0');

        if (1 !== count($files)) {
            throw new RuntimeException('Unable to find the extracted WordPress plugin');
        }

        $this->filesystem->rename($pluginsDirectory.'/'.Arr::first($files)->getFilename(), $pluginsDirectory.'/ymir-wordpress-plugin', true);
    }

    /**
     * {@inheritdoc}
     */
    public function installProject(string $directory)
    {
        $this->wpCliExecutable->downloadWordPress($directory);
    }

    /**
     * {@inheritdoc}
     */
    public function isEligibleForInstallation(string $directory): bool
    {
        try {
            return null === $this->wpCliExecutable->getVersion($directory);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isIntegrationInstalled(string $projectDirectory): bool
    {
        $pluginsPaths = [
            $projectDirectory.'/wp-content/mu-plugins',
            $projectDirectory.'/wp-content/plugins',
        ];

        $pluginsPaths = array_filter($pluginsPaths, 'is_dir');

        if (empty($pluginsPaths)) {
            return false;
        }

        $finder = Finder::create()
            ->files()
            ->in($pluginsPaths)
            ->depth('== 1')
            ->name('ymir.php')
            ->contains('Plugin Name: Ymir');

        return $finder->count() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $projectDirectory): bool
    {
        return $this->pathsExist($projectDirectory, ['/wp-config.php']);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildWordPressCorePathPattern(string $path): string
    {
        return sprintf('/^%s/', $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMustUsePluginsDirectory(): string
    {
        return 'wp-content/mu-plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPluginsDirectory(): string
    {
        return 'wp-content/plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUploadsDirectory(): string
    {
        return 'wp-content/uploads';
    }
}
