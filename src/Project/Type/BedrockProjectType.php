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

class BedrockProjectType extends AbstractWordPressProjectType implements InstallableProjectTypeInterface
{
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
            ->exclude(['web/wp/wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(string $directory): Finder
    {
        return parent::getAssetFiles(sprintf('%s/web', $directory))
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
    public function getInstallationMessage(): string
    {
        return 'Creating new Bedrock project using Composer';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Bedrock';
    }

    /**
     * {@inheritdoc}
     */
    public function installIntegration(string $directory): void
    {
        $this->composerExecutable->require('ymirapp/wordpress-plugin', $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function installProject(string $directory): void
    {
        $this->composerExecutable->createProject('roots/bedrock', $directory);
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
        return $this->composerExecutable->isPackageInstalled('ymirapp/wordpress-plugin', $directory);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $directory): bool
    {
        return $this->pathsExist($directory, ['/web/app/', '/web/wp-config.php', '/config/application.php']);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildWordPressCorePathPattern(string $path): string
    {
        return sprintf('/^web\/wp\/%s/', $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateEnvironmentConfigurationArray(string $environment, array $baseConfiguration = []): array
    {
        return Arr::add(parent::generateEnvironmentConfigurationArray($environment, $baseConfiguration), 'build', [
            'COMPOSER_MIRROR_PATH_REPOS=1 composer install'.('production' === $environment ? ' --no-dev' : ''),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMustUsePluginsDirectory(): string
    {
        return '/web/app/mu-plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPluginsDirectory(): string
    {
        return '/web/app/plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUploadsDirectory(): string
    {
        return '/web/app/uploads';
    }
}
