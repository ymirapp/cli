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
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Laravel\VaporDockerfileMigrator;
use Ymir\Cli\Project\Configuration\Laravel\VaporConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\YamlParser;

class MigrateVaporCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'laravel:vapor:migrate';

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
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, VaporDockerfileMigrator $vaporDockerfileMigrator, YamlParser $yamlParser)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->vaporDockerfileMigrator = $vaporDockerfileMigrator;
        $this->yamlParser = $yamlParser;
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
            throw new RuntimeException('No valid "environments" key found in vapor.yml file');
        }

        $matchedEnvironments = $this->getProjectConfiguration()->getEnvironments()->keys()->intersect(collect($vaporEnvironments)->keys())->values();

        if ($matchedEnvironments->isEmpty()) {
            $this->output->warning('No matching environments found between ymir.yml and vapor.yml files');

            return;
        }

        $this->getProjectConfiguration()->applyChangesToEnvironments(new VaporConfigurationChange($vaporConfiguration));

        $imageDeploymentEnvironmentConfigurations = $this->getImageDeploymentEnvironmentConfigurations($matchedEnvironments);

        if ($imageDeploymentEnvironmentConfigurations->isNotEmpty()) {
            $migrationResult = $this->migrateDockerfiles($imageDeploymentEnvironmentConfigurations);

            collect($migrationResult['created_dockerfiles'])->each(function (array $createdDockerfile): void {
                $this->output->info($this->generateDockerfileCreatedMessage($createdDockerfile));
            });
        }

        $this->output->info('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->list($matchedEnvironments);
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
        $vaporConfiguration = $this->yamlParser->parse($this->getProjectDirectory().'/vapor.yml');

        if (null === $vaporConfiguration) {
            throw new RuntimeException('Unable to migrate Vapor configuration because "vapor.yml" is missing from the project directory');
        }

        return $vaporConfiguration;
    }

    /**
     * Migrate the Dockerfiles for image deployment environments.
     */
    private function migrateDockerfiles(Collection $imageDeploymentEnvironmentConfigurations): array
    {
        return $this->output->confirm('Do you want to create one global <comment>Dockerfile</comment> for all image deployment environments?', false)
            ? $this->vaporDockerfileMigrator->migrateGlobalDockerfile($imageDeploymentEnvironmentConfigurations, $this->getProjectDirectory(), $this->getProjectConfiguration()->getProjectType())
            : $this->vaporDockerfileMigrator->migrateEnvironmentDockerfiles($imageDeploymentEnvironmentConfigurations, $this->getProjectDirectory(), $this->getProjectConfiguration()->getProjectType());
    }
}
