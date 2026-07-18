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

class RadicleProjectType extends AbstractWordPressProjectType
{
    /**
     * The Composer package containing the Laravel Ymir integration.
     */
    private const LARAVEL_INTEGRATION_PACKAGE = 'ymirapp/laravel-bridge';

    /**
     * The Composer package containing the WordPress Ymir integration.
     */
    private const WORDPRESS_INTEGRATION_PACKAGE = 'ymirapp/wordpress-plugin';

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
        return parent::getArchiveFiles($directory)
            ->exclude(['public/wp/wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(string $directory): Finder
    {
        return parent::getAssetFiles(sprintf('%s/public', $directory))
            ->exclude(['wp/wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildSteps(): array
    {
        return [
            Build\CopyProjectFilesStep::class,
            Build\WordPress\DownloadWpCliStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\WordPress\CopyMustUsePluginStep::class,
            Build\CleanupBuildStep::class,
            Build\ExtractAssetFilesStep::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Radicle';
    }

    /**
     * {@inheritdoc}
     */
    public function installIntegration(string $directory): void
    {
        $this->composerExecutable->require(self::WORDPRESS_INTEGRATION_PACKAGE, $directory);
        $this->composerExecutable->require(self::LARAVEL_INTEGRATION_PACKAGE, $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function isIntegrationConfigured(string $directory): bool
    {
        return $this->composerExecutable->requiresPackage(self::LARAVEL_INTEGRATION_PACKAGE, $directory)
            && $this->composerExecutable->requiresPackage(self::WORDPRESS_INTEGRATION_PACKAGE, $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function isIntegrationInstalled(string $directory): bool
    {
        return $this->composerExecutable->isPackageInstalled(self::LARAVEL_INTEGRATION_PACKAGE, $directory)
            && $this->composerExecutable->isPackageInstalled(self::WORDPRESS_INTEGRATION_PACKAGE, $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $directory): bool
    {
        return $this->pathsExist($directory, ['/public/content/', '/public/wp-config.php', '/bedrock/application.php']);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildWordPressCorePathPattern(string $path): string
    {
        return sprintf('/^public\/wp\/%s/', $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateEnvironmentConfigurationArray(string $environment, array $baseConfiguration = []): array
    {
        return Arr::add(parent::generateEnvironmentConfigurationArray($environment, $baseConfiguration), 'build', [
            'COMPOSER_MIRROR_PATH_REPOS=1 composer install'.('production' === $environment ? ' --no-dev' : ''),
            'yarn install && yarn build',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMustUsePluginsDirectory(): string
    {
        return '/public/content/mu-plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPluginsDirectory(): string
    {
        return '/public/content/plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUploadsDirectory(): string
    {
        return '/public/content/uploads';
    }
}
