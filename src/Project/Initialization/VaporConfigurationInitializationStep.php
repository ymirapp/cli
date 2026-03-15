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

namespace Ymir\Cli\Project\Initialization;

use Illuminate\Support\Collection;
use Ymir\Cli\Exception\YamlParseException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Laravel\VaporDockerfileMigrator;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\Configuration\Laravel\VaporConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\YamlParser;

class VaporConfigurationInitializationStep implements InitializationStepInterface
{
    /**
     * The Dockerfile migrator service.
     *
     * @var VaporDockerfileMigrator
     */
    private $vaporDockerfileMigrator;

    /**
     * The vapor configuration parser.
     *
     * @var YamlParser
     */
    private $yamlParser;

    /**
     * Constructor.
     */
    public function __construct(VaporDockerfileMigrator $vaporDockerfileMigrator, YamlParser $yamlParser)
    {
        $this->vaporDockerfileMigrator = $vaporDockerfileMigrator;
        $this->yamlParser = $yamlParser;
    }

    /**
     * {@inheritDoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        if (empty($projectRequirements['type']) || !$projectRequirements['type'] instanceof LaravelProjectType) {
            return null;
        }

        $output = $context->getOutput();
        $vaporConfigurationFilePath = $context->getProjectDirectory().'/vapor.yml';

        try {
            $vaporConfiguration = $this->yamlParser->parse($vaporConfigurationFilePath);
        } catch (YamlParseException $exception) {
            $output->warning($exception->getMessage());

            return null;
        }

        if (null === $vaporConfiguration || !$output->confirm('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)) {
            return null;
        }

        $vaporEnvironments = Arr::get($vaporConfiguration, 'environments');

        if (!is_array($vaporEnvironments)) {
            $output->warning('No valid "environments" key found in vapor.yml file');

            return null;
        }

        $environmentConfigurations = Arr::get($projectRequirements, 'environment_configurations');

        if (!$environmentConfigurations instanceof Collection) {
            return null;
        }

        $matchedEnvironments = $environmentConfigurations->keys()->intersect(collect($vaporEnvironments)->keys())->values();

        if ($matchedEnvironments->isEmpty()) {
            $output->warning('No matching environments found between ymir.yml and vapor.yml files');

            return null;
        }

        $vaporConfigurationChange = new VaporConfigurationChange($vaporConfiguration);

        $imageDeploymentEnvironmentConfigurations = $this->getImageDeploymentEnvironmentConfigurations($matchedEnvironments, $environmentConfigurations, $projectRequirements['type'], $vaporConfigurationChange);

        if ($imageDeploymentEnvironmentConfigurations->isNotEmpty() && $output->confirm('Vapor migration affects image deployment environments. Do you also want to migrate/create Dockerfile(s) now?', false)) {
            $migrationResult = $this->migrateDockerfiles($imageDeploymentEnvironmentConfigurations, $context, $projectRequirements['type']);

            collect($migrationResult['created_dockerfiles'])->each(function (array $createdDockerfile) use ($output): void {
                $output->info($this->generateDockerfileCreatedMessage($createdDockerfile));
            });
        }

        $output->info('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $output->list($matchedEnvironments);

        return $vaporConfigurationChange;
    }

    /**
     * Generate the success message after creating the Dockerfile.
     */
    private function generateDockerfileCreatedMessage(array $createdDockerfile): string
    {
        return sprintf('Created <comment>%s</comment> for PHP <comment>%s</comment> and <comment>%s</comment> architecture', $createdDockerfile['name'], $createdDockerfile['php_version'], $createdDockerfile['architecture']);
    }

    /**
     * Get the image deployment environment configurations to use for Dockerfile migration.
     */
    private function getImageDeploymentEnvironmentConfigurations(Collection $matchedEnvironments, Collection $environmentConfigurations, ProjectTypeInterface $projectType, VaporConfigurationChange $vaporConfigurationChange): Collection
    {
        return $matchedEnvironments
            ->map(function (string $environment) use ($environmentConfigurations, $projectType, $vaporConfigurationChange): ?EnvironmentConfiguration {
                $environmentConfiguration = $environmentConfigurations->get($environment);

                if (!$environmentConfiguration instanceof EnvironmentConfiguration) {
                    return null;
                }

                return $vaporConfigurationChange->apply($environmentConfiguration, $projectType);
            })
            ->filter(function ($environmentConfiguration): bool {
                return $environmentConfiguration instanceof EnvironmentConfiguration
                    && $environmentConfiguration->isImageDeploymentType();
            })
            ->values();
    }

    /**
     * Migrate the Dockerfiles for image deployment environments.
     */
    private function migrateDockerfiles(Collection $imageDeploymentEnvironmentConfigurations, ExecutionContext $context, ProjectTypeInterface $projectType): array
    {
        return $context->getOutput()->confirm('Do you want to create one global <comment>Dockerfile</comment> for all image deployment environments?', false)
            ? $this->vaporDockerfileMigrator->migrateGlobalDockerfile($imageDeploymentEnvironmentConfigurations, $context->getProjectDirectory(), $projectType)
            : $this->vaporDockerfileMigrator->migrateEnvironmentDockerfiles($imageDeploymentEnvironmentConfigurations, $context->getProjectDirectory(), $projectType);
    }
}
