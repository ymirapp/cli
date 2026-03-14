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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\Project\Build;
use Ymir\Cli\Support\Arr;

class LaravelProjectType extends AbstractProjectType implements InstallableProjectTypeInterface
{
    /**
     * The paths that we want to exclude when deploying a Laravel project.
     *
     * @see Finder::path()
     */
    private const EXCLUDED_PATHS = [
        '/\.env(?!.*\.encrypted$).*/',
        '/bootstrap\/cache\/routes.*\.php/',
        '/database\/.*\.sqlite/',
        '/^bootstrap\/cache\/config\.php$/',
        '/^public\/storage\/.+$/',
        '/^resources\/assets\/.+$/',
        '/^resources\/css\/.+$/',
        '/^resources\/images\/.+$/',
        '/^resources\/js\/.+$/',
        '/^storage\/.+$/',
        '/^tests\/.+$/',
    ];

    /**
     * The Composer executable.
     *
     * @var ComposerExecutable
     */
    private $composerExecutable;

    /**
     * Constructor.
     */
    public function __construct(ComposerExecutable $composerExecutable, Filesystem $filesystem)
    {
        parent::__construct($filesystem);

        $this->composerExecutable = $composerExecutable;
    }

    /**
     * {@inheritdoc}
     */
    public function getArchiveFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(string $directory): Finder
    {
        return parent::getAssetFiles($directory.'/public');
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildSteps(): array
    {
        return [
            Build\CopyProjectFilesStep::class,
            Build\Laravel\SetupBuildEnvironmentStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\CleanupBuildStep::class,
            Build\ExtractAssetFilesStep::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPhpVersion(): string
    {
        return '8.3';
    }

    /**
     * {@inheritDoc}
     */
    public function getExcludedFiles(string $directory): Finder
    {
        return parent::getExcludedFiles($directory)
            ->append($this->getExcludedPaths($directory));
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallationMessage(): string
    {
        return 'Creating new Laravel project using Composer';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Laravel';
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectFiles(string $directory): Finder
    {
        return parent::getProjectFiles($directory)
            ->append($this->getConfigurationFiles($directory));
    }

    /**
     * {@inheritdoc}
     */
    public function installIntegration(string $directory): void
    {
        $this->composerExecutable->require('ymirapp/laravel-bridge', $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function installProject(string $directory): void
    {
        $this->composerExecutable->createProject('laravel/laravel', $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function isEligibleForInstallation(string $directory): bool
    {
        return !(new \FilesystemIterator($directory))->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function isIntegrationInstalled(string $directory): bool
    {
        return $this->composerExecutable->isPackageInstalled('ymirapp/laravel-bridge', $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $directory): bool
    {
        return $this->pathsExist($directory, ['/artisan', '/composer.json', '/public/index.php'])
            && $this->composerExecutable->isPackageInstalled('laravel/framework', $directory);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateEnvironmentConfigurationArray(string $environment, array $baseConfiguration = []): array
    {
        $configuration = parent::generateEnvironmentConfigurationArray($environment, $baseConfiguration);

        $configuration = Arr::add($configuration, 'build', [
            'COMPOSER_MIRROR_PATH_REPOS=1 composer install'.('production' === $environment ? ' --no-dev' : ''),
            'npm ci && npm run build',
        ]);

        $configuration = Arr::add($configuration, 'cdn.cookies_whitelist', [
            'laravel_session',
            'laravel-session',
            'remember_*',
            'XSRF-TOKEN',
        ]);

        $configuration = Arr::add($configuration, 'cdn.excluded_paths', [
            '/login',
        ]);

        $configuration = Arr::add($configuration, 'queues', true);

        return $configuration;
    }

    /**
     * Get the Finder object for finding all the Laravel configuration files in the given project directory.
     */
    private function getConfigurationFiles(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
            ->ignoreDotFiles(false)
            ->files()
            ->depth('==0')
            ->name('.env*')
            ->notName('.env.example');
    }

    private function getExcludedPaths(string $directory): Finder
    {
        return $this->getBaseFinder($directory)
            ->ignoreDotFiles(false)
            ->path(self::EXCLUDED_PATHS);
    }
}
