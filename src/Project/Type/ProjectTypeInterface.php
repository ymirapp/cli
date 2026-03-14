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

use Symfony\Component\Finder\Finder;
use Ymir\Cli\Project\EnvironmentConfiguration;

interface ProjectTypeInterface
{
    /**
     * Generate the configuration for the project type for the given environment.
     */
    public function generateEnvironmentConfiguration(string $environment, array $baseConfiguration = []): EnvironmentConfiguration;

    /**
     * Get the Finder object for finding all the files that should be included in the deployment archive.
     */
    public function getArchiveFiles(string $directory): Finder;

    /**
     * Get the Finder object for finding all the asset files that we have to extract.
     */
    public function getAssetFiles(string $directory): Finder;

    /**
     * Get the build steps for the project type.
     */
    public function getBuildSteps(): array;

    /**
     * Get the default PHP version for the project type.
     */
    public function getDefaultPhpVersion(): string;

    /**
     * Get the Finder object for finding all the files that we want to exclude from the final build.
     */
    public function getExcludedFiles(string $directory): Finder;

    /**
     * Get the Finder object for finding all the files that we want to include in the final build.
     */
    public function getIncludedFiles(string $directory, array $paths): Finder;

    /**
     * Get the initialization steps when initializing the project type.
     */
    public function getInitializationSteps(): array;

    /**
     * Get the project type name.
     */
    public function getName(): string;

    /**
     * Get the Finder object for finding all the project files.
     */
    public function getProjectFiles(string $directory): Finder;

    /**
     * Get the project type slug.
     */
    public function getSlug(): string;

    /**
     * Install the Ymir integration.
     */
    public function installIntegration(string $directory): void;

    /**
     * Check if the Ymir integration is installed.
     */
    public function isIntegrationInstalled(string $directory): bool;

    /**
     * Determine whether the project in the given directory matches this project type.
     */
    public function matchesProject(string $directory): bool;
}
