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
    public function getAssetFiles(string $projectDirectory): Finder
    {
        return parent::getAssetFiles(sprintf('%s/public', $projectDirectory))
            ->exclude(['wp/wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildFiles(string $projectDirectory): Finder
    {
        return parent::getBuildFiles($projectDirectory)
            ->exclude(['public/wp/wp-content']);
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
    public function installIntegration(string $projectDirectory): void
    {
        $this->composerExecutable->require('ymirapp/wordpress-plugin', $projectDirectory);
        $this->composerExecutable->require('ymirapp/laravel-bridge', $projectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function isIntegrationInstalled(string $projectDirectory): bool
    {
        return $this->composerExecutable->isPackageInstalled('ymirapp/laravel-bridge', $projectDirectory)
            && $this->composerExecutable->isPackageInstalled('ymirapp/wordpress-plugin', $projectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $projectDirectory): bool
    {
        return $this->pathsExist($projectDirectory, ['/public/content/', '/public/wp-config.php', '/bedrock/application.php']);
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
            'yarn install && yarn build && rm -rf node_modules',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMustUsePluginsDirectory(): string
    {
        return 'public/content/mu-plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPluginsDirectory(): string
    {
        return 'public/content/plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUploadsDirectory(): string
    {
        return 'public/content/uploads';
    }
}
