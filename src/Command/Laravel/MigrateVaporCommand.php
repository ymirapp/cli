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

namespace Ymir\Cli\Command\Laravel;

use Illuminate\Support\Collection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Configuration\VaporConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Support\Arr;

class MigrateVaporCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'laravel:vapor:migrate';

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
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Dockerfile $dockerfile, Filesystem $filesystem)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->dockerfile = $dockerfile;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Migrate vapor.yml environment configuration into ymir.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (!$this->getProjectConfiguration()->getProjectType() instanceof LaravelProjectType) {
            throw new UnsupportedProjectException('You can only use this command with Laravel projects');
        }

        $vaporConfiguration = $this->getVaporConfiguration();
        $vaporEnvironments = Arr::get($vaporConfiguration, 'environments');

        if (!is_array($vaporEnvironments)) {
            throw new InvalidInputException('No valid "environments" key found in vapor.yml file');
        }

        $matchedEnvironments = $this->getProjectConfiguration()->getEnvironments()->keys()->intersect(collect($vaporEnvironments)->keys())->values();

        if ($matchedEnvironments->isEmpty()) {
            $this->output->warning('No matching environments found between ymir.yml and vapor.yml files');

            return;
        }

        $this->getProjectConfiguration()->applyChangesToEnvironments(new VaporConfigurationChange($vaporConfiguration));

        $imageDeploymentEnvironmentConfigurations = $this->getImageDeploymentEnvironmentConfigurations($matchedEnvironments);

        if (!$imageDeploymentEnvironmentConfigurations->isEmpty()) {
            $this->migrateDockerfiles($imageDeploymentEnvironmentConfigurations);
        }

        $this->output->info('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->list($matchedEnvironments);
    }

    /**
     * Back up all relevant Dockerfiles before migration.
     */
    private function backupDockerfiles(Collection $environmentConfigurations, bool $backupGlobalDockerfile): void
    {
        $dockerfilePaths = $environmentConfigurations
            ->map(function (EnvironmentConfiguration $environmentConfiguration): string {
                return $this->generateDockerfilePath($environmentConfiguration->getName());
            })
            ->unique();

        if ($backupGlobalDockerfile) {
            $dockerfilePaths->push($this->generateDockerfilePath());
        }

        $dockerfilePaths->filter(function (string $dockerfilePath): bool {
            return $this->filesystem->exists($dockerfilePath);
        })->each(function (string $dockerfilePath): void {
            $this->filesystem->rename($dockerfilePath, $dockerfilePath.'.bak', true);
        });
    }

    /**
     * Create a Dockerfile.
     */
    private function createDockerfile(EnvironmentConfiguration $environmentConfiguration, string $phpVersion, bool $globalDockerfile): void
    {
        $architecture = $environmentConfiguration->getArchitecture() ?: 'x86_64';
        $environment = $globalDockerfile ? '' : $environmentConfiguration->getName();

        $this->dockerfile->create($architecture, $phpVersion, $environment);

        $this->output->info($this->generateDockerfileCreatedMessage($architecture, $environment, $phpVersion));

        if ($globalDockerfile) {
            $this->output->comment(sprintf('Using <comment>%s</comment> environment configuration', $environmentConfiguration->getName()));
        }
    }

    /**
     * Generate the success message after creating the Dockerfile.
     */
    private function generateDockerfileCreatedMessage(string $architecture, string $environment, string $phpVersion): string
    {
        return sprintf('Created <comment>%s</comment> for PHP <comment>%s</comment> and <comment>%s</comment> architecture', Dockerfile::getFileName($environment), $phpVersion, $architecture);
    }

    /**
     * Generate the full Dockerfile path.
     */
    private function generateDockerfilePath(string $environment = ''): string
    {
        return sprintf('%s/%s', $this->getProjectDirectory(), Dockerfile::getFileName($environment));
    }

    /**
     * Get the fallback PHP version from environment or project type.
     */
    private function getFallbackPhpVersion(EnvironmentConfiguration $environmentConfiguration): string
    {
        return empty($environmentConfiguration->getPhpVersion()) ? $this->getProjectConfiguration()->getProjectType()->getDefaultPhpVersion() : $environmentConfiguration->getPhpVersion();
    }

    /**
     * Get the image deployment environment configurations to use for Dockerfile migration.
     */
    private function getImageDeploymentEnvironmentConfigurations(Collection $matchedEnvironments): Collection
    {
        return $matchedEnvironments
            ->map(function (string $environment): EnvironmentConfiguration {
                return $this->getProjectConfiguration()->getEnvironmentConfiguration($environment);
            })
            ->filter(function (EnvironmentConfiguration $environmentConfiguration): bool {
                return $environmentConfiguration->isImageDeploymentType();
            })
            ->values();
    }

    /**
     * Get the parsed Vapor configuration.
     */
    private function getVaporConfiguration(): array
    {
        $vaporConfigurationFilePath = $this->getProjectDirectory().'/vapor.yml';

        if (!$this->filesystem->exists($vaporConfigurationFilePath)) {
            throw new InvalidInputException(sprintf('No vapor configuration file found at "%s"', $vaporConfigurationFilePath));
        }

        try {
            $vaporConfiguration = Yaml::parse((string) file_get_contents($vaporConfigurationFilePath));
        } catch (\Throwable $exception) {
            throw new InvalidInputException(sprintf('Error parsing Vapor configuration file: %s', $exception->getMessage()));
        }

        if (!is_array($vaporConfiguration)) {
            throw new InvalidInputException('Error parsing Vapor configuration file');
        }

        return $vaporConfiguration;
    }

    /**
     * Migrate Dockerfiles for image deployment environments.
     */
    private function migrateDockerfiles(Collection $environmentConfigurations): void
    {
        $phpVersions = $environmentConfigurations->mapWithKeys(function (EnvironmentConfiguration $environmentConfiguration): array {
            return [$environmentConfiguration->getName() => $this->resolvePhpVersion($environmentConfiguration, $this->generateDockerfilePath($environmentConfiguration->getName()))];
        });
        $createGlobalDockerfile = $this->output->confirm('Do you want to create one global <comment>Dockerfile</comment> for all image deployment environments?', false);
        $sourceEnvironmentConfiguration = $this->selectDockerfileSourceEnvironmentConfiguration($environmentConfigurations);
        $dockerfileConfigurations = $createGlobalDockerfile ? collect([$sourceEnvironmentConfiguration]) : $environmentConfigurations;

        $this->backupDockerfiles($environmentConfigurations, $createGlobalDockerfile);

        $dockerfileConfigurations->each(function (EnvironmentConfiguration $environmentConfiguration) use ($createGlobalDockerfile, $phpVersions): void {
            $this->createDockerfile($environmentConfiguration, (string) $phpVersions->get($environmentConfiguration->getName()), $createGlobalDockerfile);
        });
    }

    /**
     * Resolve the PHP version from the existing Dockerfile content.
     */
    private function resolvePhpVersion(EnvironmentConfiguration $environmentConfiguration, string $dockerfilePath): string
    {
        if (!$this->filesystem->exists($dockerfilePath)) {
            return $this->getFallbackPhpVersion($environmentConfiguration);
        }

        $dockerfileContent = (string) file_get_contents($dockerfilePath);

        if (1 !== preg_match('/:php-?(?:(\d+)\.(\d+)|(\d{2,3}))/i', $dockerfileContent, $matches)) {
            return $this->getFallbackPhpVersion($environmentConfiguration);
        } elseif (!empty($matches[1]) && !empty($matches[2])) {
            return sprintf('%s.%s', $matches[1], $matches[2]);
        } elseif (empty($matches[3])) {
            return $this->getFallbackPhpVersion($environmentConfiguration);
        }

        $version = $matches[3];

        return 2 === strlen($version) ? sprintf('%s.%s', $version[0], $version[1]) : sprintf('%s.%s', $version[0], substr($version, 1));
    }

    /**
     * Select the source environment for Dockerfile generation.
     */
    private function selectDockerfileSourceEnvironmentConfiguration(Collection $environmentConfigurations): EnvironmentConfiguration
    {
        $productionEnvironmentConfiguration = $environmentConfigurations->first(function (EnvironmentConfiguration $environmentConfiguration): bool {
            return 'production' === $environmentConfiguration->getName();
        });

        return $productionEnvironmentConfiguration instanceof EnvironmentConfiguration ? $productionEnvironmentConfiguration : $environmentConfigurations->first();
    }
}
