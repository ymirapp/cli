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

interface ProjectTypeInterface
{
    /**
     * Get the Finder object for finding all the asset files that we have to extract in the given project directory.
     */
    public function getAssetFiles(string $projectDirectory): Finder;

    /**
     * Get the Finder object for finding all the files necessary for a build in the given project directory.
     */
    public function getBuildFiles(string $projectDirectory): Finder;

    /**
     * Get the build steps for the project type.
     */
    public function getBuildSteps(): array;

    /**
     * Get the configuration for the project type for the given environment.
     */
    public function getEnvironmentConfiguration(string $environment, array $baseConfiguration = []): array;

    /**
     * Get the project type name.
     */
    public function getName(): string;

    /**
     * Get the Finder object for finding all the files in the given project directory.
     */
    public function getProjectFiles(string $projectDirectory): Finder;

    /**
     * Get the project type slug.
     */
    public function getSlug(): string;

    /**
     * Install the Ymir integration in the given project directory.
     */
    public function installIntegration(string $projectDirectory);

    /**
     * Check if the Ymir integration is installed in the given project directory.
     */
    public function isIntegrationInstalled(string $projectDirectory): bool;

    /**
     * Determine whether the project at the given project directory matches this project type.
     */
    public function matchesProject(string $projectDirectory): bool;
}
