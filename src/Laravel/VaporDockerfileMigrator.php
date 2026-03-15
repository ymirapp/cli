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

namespace Ymir\Cli\Laravel;

use Illuminate\Support\Collection;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;

class VaporDockerfileMigrator
{
    /**
     * The default architecture for a Dockerfile.
     */
    private const DEFAULT_ARCHITECTURE = 'x86_64';

    /**
     * The project Dockerfile service.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(Dockerfile $dockerfile, Filesystem $filesystem)
    {
        $this->dockerfile = $dockerfile;
        $this->filesystem = $filesystem;
    }

    /**
     * Migrate Dockerfiles for image deployment environments to environment-specific Dockerfiles.
     */
    public function migrateEnvironmentDockerfiles(Collection $environmentConfigurations, string $projectDirectory, ProjectTypeInterface $projectType): array
    {
        if ($environmentConfigurations->isEmpty()) {
            return $this->generateEmptyMigrationResult();
        }

        $phpVersions = $this->resolvePhpVersions($environmentConfigurations, $projectDirectory, $projectType);

        $backupDockerfilePaths = $this->backupEnvironmentDockerfiles($environmentConfigurations, $projectDirectory);
        $createdDockerfiles = $environmentConfigurations->map(function (EnvironmentConfiguration $environmentConfiguration) use ($phpVersions): array {
            return $this->createDockerfile($environmentConfiguration, (string) $phpVersions->get($environmentConfiguration->getName()), $environmentConfiguration->getName());
        })->values();

        return [
            'created_dockerfiles' => $createdDockerfiles->all(),
            'backed_up_dockerfile_paths' => $backupDockerfilePaths->all(),
        ];
    }

    /**
     * Migrate Dockerfiles for image deployment environments to one global Dockerfile.
     */
    public function migrateGlobalDockerfile(Collection $environmentConfigurations, string $projectDirectory, ProjectTypeInterface $projectType): array
    {
        if ($environmentConfigurations->isEmpty()) {
            return $this->generateEmptyMigrationResult();
        }

        $sourceEnvironmentConfiguration = $this->selectDockerfileSourceEnvironmentConfiguration($environmentConfigurations);
        $sourceEnvironmentPhpVersion = $this->resolvePhpVersion($sourceEnvironmentConfiguration, $this->generateDockerfilePath($projectDirectory, $sourceEnvironmentConfiguration->getName()), $projectType);

        $backupDockerfilePaths = $this->backupGlobalDockerfileMigrationDockerfiles($environmentConfigurations, $projectDirectory);
        $createdDockerfiles = [
            $this->createDockerfile($sourceEnvironmentConfiguration, $sourceEnvironmentPhpVersion, ''),
        ];

        return [
            'created_dockerfiles' => $createdDockerfiles,
            'backed_up_dockerfile_paths' => $backupDockerfilePaths->all(),
        ];
    }

    /**
     * Back up Dockerfiles at the given paths.
     */
    private function backupDockerfilesByPaths(Collection $dockerfilePaths): Collection
    {
        return $dockerfilePaths
            ->unique()
            ->filter(function (string $dockerfilePath): bool {
                return $this->filesystem->exists($dockerfilePath);
            })->each(function (string $dockerfilePath): void {
                $this->filesystem->rename($dockerfilePath, $dockerfilePath.'.bak', true);
            });
    }

    /**
     * Back up Dockerfiles used by environment migration.
     */
    private function backupEnvironmentDockerfiles(Collection $environmentConfigurations, string $projectDirectory): Collection
    {
        return $this->backupDockerfilesByPaths(
            $environmentConfigurations
                ->map(function (EnvironmentConfiguration $environmentConfiguration) use ($projectDirectory): string {
                    return $this->generateDockerfilePath($projectDirectory, $environmentConfiguration->getName());
                })
        );
    }

    /**
     * Back up Dockerfiles used by global migration.
     */
    private function backupGlobalDockerfileMigrationDockerfiles(Collection $environmentConfigurations, string $projectDirectory): Collection
    {
        return $this->backupDockerfilesByPaths(
            $environmentConfigurations
                ->map(function (EnvironmentConfiguration $environmentConfiguration) use ($projectDirectory): string {
                    return $this->generateDockerfilePath($projectDirectory, $environmentConfiguration->getName());
                })
                ->push($this->generateDockerfilePath($projectDirectory))
        );
    }

    /**
     * Create a Dockerfile and return its metadata.
     */
    private function createDockerfile(EnvironmentConfiguration $environmentConfiguration, string $phpVersion, string $environment): array
    {
        $architecture = $this->getDockerfileArchitecture($environmentConfiguration);

        $this->dockerfile->create($architecture, $phpVersion, $environment);

        return [
            'architecture' => $architecture,
            'environment' => $environment,
            'name' => Dockerfile::getFileName($environment),
            'php_version' => $phpVersion,
        ];
    }

    /**
     * Generate the full Dockerfile path.
     */
    private function generateDockerfilePath(string $projectDirectory, string $environment = ''): string
    {
        return sprintf('%s/%s', $projectDirectory, Dockerfile::getFileName($environment));
    }

    /**
     * Generate an empty migration result.
     */
    private function generateEmptyMigrationResult(): array
    {
        return [
            'created_dockerfiles' => [],
            'backed_up_dockerfile_paths' => [],
        ];
    }

    /**
     * Get the Dockerfile architecture for an environment.
     */
    private function getDockerfileArchitecture(EnvironmentConfiguration $environmentConfiguration): string
    {
        return $environmentConfiguration->getArchitecture() ?: self::DEFAULT_ARCHITECTURE;
    }

    /**
     * Get the fallback PHP version from environment or project type.
     */
    private function getFallbackPhpVersion(EnvironmentConfiguration $environmentConfiguration, ProjectTypeInterface $projectType): string
    {
        return $environmentConfiguration->getPhpVersion() ?: $projectType->getDefaultPhpVersion();
    }

    /**
     * Resolve the PHP version from the existing Dockerfile content.
     */
    private function resolvePhpVersion(EnvironmentConfiguration $environmentConfiguration, string $dockerfilePath, ProjectTypeInterface $projectType): string
    {
        $fallbackPhpVersion = $this->getFallbackPhpVersion($environmentConfiguration, $projectType);

        if (!$this->filesystem->exists($dockerfilePath)) {
            return $fallbackPhpVersion;
        }

        $dockerfileContent = file_get_contents($dockerfilePath);

        if (false === $dockerfileContent) {
            return $fallbackPhpVersion;
        }

        return $this->resolvePhpVersionFromDockerfileContent($dockerfileContent, $fallbackPhpVersion);
    }

    /**
     * Resolve the PHP version from Dockerfile content.
     */
    private function resolvePhpVersionFromDockerfileContent(string $dockerfileContent, string $fallbackPhpVersion): string
    {
        $matches = [];

        return 1 === preg_match('/laravelphp\/vapor:php(\d{2})\b/i', $dockerfileContent, $matches) ? sprintf('%s.%s', $matches[1][0], $matches[1][1]) : $fallbackPhpVersion;
    }

    /**
     * Resolve PHP versions for all environment configurations.
     */
    private function resolvePhpVersions(Collection $environmentConfigurations, string $projectDirectory, ProjectTypeInterface $projectType): Collection
    {
        return $environmentConfigurations->mapWithKeys(function (EnvironmentConfiguration $environmentConfiguration) use ($projectDirectory, $projectType): array {
            return [$environmentConfiguration->getName() => $this->resolvePhpVersion($environmentConfiguration, $this->generateDockerfilePath($projectDirectory, $environmentConfiguration->getName()), $projectType)];
        });
    }

    /**
     * Select the source environment for Dockerfile generation.
     */
    private function selectDockerfileSourceEnvironmentConfiguration(Collection $environmentConfigurations): EnvironmentConfiguration
    {
        $productionEnvironmentConfiguration = $environmentConfigurations->first(function (EnvironmentConfiguration $environmentConfiguration): bool {
            return 'production' === $environmentConfiguration->getName();
        });

        if ($productionEnvironmentConfiguration instanceof EnvironmentConfiguration) {
            return $productionEnvironmentConfiguration;
        }

        $sourceEnvironmentConfiguration = $environmentConfigurations->first();

        if (!$sourceEnvironmentConfiguration instanceof EnvironmentConfiguration) {
            throw new RuntimeException('No environment configuration available for Vapor Dockerfile migration');
        }

        return $sourceEnvironmentConfiguration;
    }
}
