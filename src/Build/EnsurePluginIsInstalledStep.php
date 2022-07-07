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
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class EnsurePluginIsInstalledStep extends AbstractBuildStep
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
        return 'Ensuring Ymir plugin is installed';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $pluginsPaths = collect('bedrock' !== $projectConfiguration->getProjectType() ? ['/wp-content/mu-plugins', '/wp-content/plugins'] : ['/web/app/plugins', '/web/app/mu-plugins'])
            ->map(function (string $relativePath) {
                return $this->buildDirectory.$relativePath;
            })->filter(function (string $path) {
                return is_dir($path);
            })->all();

        if (empty($pluginsPaths)) {
            throw new RuntimeException('No "plugins" or "mu-plugins" directory found in build directory');
        }

        $finder = Finder::create()
                        ->files()
                        ->in($pluginsPaths)
                        ->depth('== 1')
                        ->name('ymir.php')
                        ->contains('Plugin Name: Ymir');

        if (0 === $finder->count()) {
            throw new RuntimeException('Ymir plugin not found in build directory');
        }
    }
}
