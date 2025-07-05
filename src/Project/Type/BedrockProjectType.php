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
use Ymir\Cli\Build;
use Ymir\Cli\Executable\ComposerExecutable;
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
    public function getAssetFiles(string $projectDirectory): Finder
    {
        return parent::getAssetFiles(sprintf('%s/web', $projectDirectory))
            ->exclude(['wp/wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuildFiles(string $projectDirectory): Finder
    {
        return parent::getBuildFiles($projectDirectory)
            ->exclude(['web/wp/wp-content']);
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
    public function getEnvironmentConfiguration(string $environment, array $baseConfiguration = []): array
    {
        return Arr::add(parent::getEnvironmentConfiguration($environment, $baseConfiguration), 'build', [
            'COMPOSER_MIRROR_PATH_REPOS=1 composer install'.('production' === $environment ? ' --no-dev' : ''),
        ]);
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
    public function installIntegration(string $projectDirectory)
    {
        $this->composerExecutable->require('ymirapp/wordpress-plugin', $projectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function installProject(string $directory)
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
    public function isIntegrationInstalled(string $projectDirectory): bool
    {
        return $this->composerExecutable->isPackageInstalled('ymirapp/wordpress-plugin', $projectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesProject(string $projectDirectory): bool
    {
        return $this->pathsExist($projectDirectory, ['/web/app/', '/web/wp-config.php', '/config/application.php']);
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
    protected function getMustUsePluginsDirectory(): string
    {
        return 'web/app/mu-plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPluginsDirectory(): string
    {
        return 'web/app/plugins';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUploadsDirectory(): string
    {
        return 'web/app/uploads';
    }
}
