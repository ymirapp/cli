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

namespace Ymir\Cli\Command\Project;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Database\CreateDatabaseCommand;
use Ymir\Cli\Command\Database\CreateDatabaseServerCommand;
use Ymir\Cli\Command\Docker\CreateDockerfileCommand;
use Ymir\Cli\Command\InstallPluginCommand;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

class InitializeProjectCommand extends AbstractCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'init';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:init';

    /**
     * The Composer executable.
     *
     * @var ComposerExecutable
     */
    private $composerExecutable;

    /**
     * Docker executable.
     *
     * @var DockerExecutable
     */
    private $dockerExecutable;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * The WP-CLI executable.
     *
     * @var WpCliExecutable
     */
    private $wpCliExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ComposerExecutable $composerExecutable, DockerExecutable $dockerExecutable, Filesystem $filesystem, ProjectConfiguration $projectConfiguration, string $projectDirectory, WpCliExecutable $wpCliExecutable)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->composerExecutable = $composerExecutable;
        $this->dockerExecutable = $dockerExecutable;
        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
        $this->wpCliExecutable = $wpCliExecutable;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Initialize a new project')
            ->setAliases([self::ALIAS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function determineCloudProvider(string $question): int
    {
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if ($providers->isEmpty()) {
            $this->output->info('Connecting to a cloud provider');

            $this->retryApi(function () {
                $this->invoke(ConnectProviderCommand::NAME);
            }, 'Do you want to try to connect to a cloud provider again?');
        }

        return parent::determineCloudProvider($question);
    }

    /**
     * {@inheritdoc}
     */
    protected function mustBeInteractive(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if ($this->projectConfiguration->exists()
            && !$this->output->confirm('A project already exists in this directory. Do you want to overwrite it?', false)
        ) {
            return;
        } elseif ($this->projectConfiguration->exists()) {
            $this->projectConfiguration->delete();
        }

        $this->retryApi(function () {
            $projectName = $this->output->askSlug('What is the name of the project', basename(getcwd() ?: '') ?: null);
            $projectType = $this->determineProjectType();
            $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider that the project will use');
            $region = $this->determineRegion('Enter the name of the region that the project will be in', $providerId);

            // Define the environments now so we check for the database server before checking for WordPress
            $environments = $this->addEnvironmentDatabaseNodes($this->getBaseEnvironmentsConfiguration($projectType), $projectName, $region);

            // This needs to happen before we create the configuration file because "composer create-project"
            // needs an empty directory.
            $this->checkForWordPress($projectType);

            $this->projectConfiguration->createNew($this->apiClient->createProject($providerId, $projectName, $region, $environments->keys()->all()), $environments->all(), $projectType);

            $this->output->infoWithDelayWarning('Project initialized');

            if (!$this->isPluginInstalled($projectType) && $this->output->confirm('Would you like to install the Ymir WordPress plugin?')) {
                $this->invoke(InstallPluginCommand::NAME);
            }

            $useContainerImage = $this->output->confirm('Do you want to deploy this project using a container image?');

            if ($useContainerImage) {
                $this->invoke(CreateDockerfileCommand::NAME, ['--configure-project' => null]);
            }

            if ($useContainerImage && !$this->dockerExecutable->isInstalled()) {
                $this->output->warning('Docker wasn\'t detected on this computer. You won\'t be able to deploy this project locally.');
            }

            if ($this->wpCliExecutable->isInstalled() && $this->wpCliExecutable->isWordPressInstalled() && $this->output->confirm('Do you want to have Ymir scan your plugins and themes and configure your project?')) {
                $this->invoke(ConfigureProjectCommand::NAME);
            }
        }, 'Do you want to try creating a project again?');
    }

    /**
     * Add the "database" nodes to all the environments.
     */
    private function addEnvironmentDatabaseNodes(Collection $environments, string $projectName, string $region): Collection
    {
        $databaseServer = $this->determineDatabaseServer($region);

        if (empty($databaseServer['name'])) {
            return $environments;
        }

        $databasePrefix = trim($this->output->askSlug('What database prefix would you like to use for this project?', $projectName));
        $environments = $environments->map(function (array $options, string $environment) use ($databasePrefix, $databaseServer) {
            Arr::set($options, 'database.server', $databaseServer['name']);
            Arr::set($options, 'database.name', $databasePrefix ? sprintf('%s_%s', rtrim($databasePrefix, '_'), $environment) : $environment);

            return $options;
        });

        if (!empty($databaseServer['publicly_accessible']) && $this->output->confirm(sprintf('Would you like to create the staging and production databases for your project on the "<comment>%s</comment>" database server?', $databaseServer['name']))) {
            $environments->each(function (array $options) {
                $this->invoke(CreateDatabaseCommand::NAME, ['name' => Arr::get($options, 'database.name'), '--server' => Arr::get($options, 'database.server')], new NullOutput());
            });
        }

        return $environments;
    }

    /**
     * Check for WordPress and offer to install it if it's not detected.
     */
    private function checkForWordPress(string $projectType)
    {
        if (!$this->isWordPressDownloadable($projectType) || !$this->output->confirm('WordPress wasn\'t detected in the project directory. Would you like to download it?')) {
            return;
        }

        if ('bedrock' === $projectType) {
            $this->output->info('Creating new Bedrock project');
            $this->composerExecutable->createProject('roots/bedrock');
        } elseif ('wordpress' === $projectType) {
            $this->output->info('Downloading WordPress using WP-CLI');
            $this->wpCliExecutable->downloadWordPress();
        }

        $this->output->info('WordPress downloaded successfully');
    }

    /**
     * Determine the database server to use for this project.
     */
    private function determineDatabaseServer(string $region): ?array
    {
        $database = null;
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->where('region', $region)->whereNotIn('status', ['deleting', 'failed'])->values();

        if (!$databases->isEmpty() && $this->output->confirm('Would you like to use an existing database server for this project?')) {
            $database = $this->output->choiceWithResourceDetails('Which database server would you like to use?', $databases);
        } elseif (
            (!$databases->isEmpty() && $this->output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $this->output->confirm(sprintf('Your team doesn\'t have any configured database servers in the "<comment>%s</comment>" region. Would you like to create one for this team first?', $region)))
        ) {
            $this->retryApi(function () {
                $this->invoke(CreateDatabaseServerCommand::NAME);
            }, 'Do you want to try creating a database server again?');

            return $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->last();
        }

        return $databases->firstWhere('name', $database);
    }

    /**
     * Determine the type of project being initialized.
     */
    private function determineProjectType(): string
    {
        $type = '';

        if ($this->projectPathsExist(['/wp-config.php'])) {
            $type = 'wordpress';
        } elseif ($this->projectPathsExist(['/web/app/', '/web/wp-config.php', '/config/application.php'])) {
            $type = 'bedrock';
        } elseif ($this->projectPathsExist(['/public/content/', '/public/wp-config.php', '/bedrock/application.php'])) {
            $type = 'radicle';
        }

        if (empty($type)) {
            $type = $this->output->choice('Please select the type of project to initialize', ['Bedrock', 'Radicle', 'WordPress'], 'WordPress');
        }

        return strtolower($type);
    }

    /**
     * Get the base environments configuration for the project.
     */
    private function getBaseEnvironmentsConfiguration(string $projectType): Collection
    {
        $environments = [
            'production' => [
                'architecture' => 'arm64',
            ],
            'staging' => [
                'architecture' => 'arm64',
                'cdn' => ['caching' => 'assets'],
                'cron' => false,
                'warmup' => false,
            ],
        ];

        if ('bedrock' === $projectType) {
            Arr::set($environments, 'production.build', ['COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev']);
            Arr::set($environments, 'staging.build', ['COMPOSER_MIRROR_PATH_REPOS=1 composer install']);
        } elseif ('radicle' === $projectType) {
            Arr::set($environments, 'production.build', [
                'composer install --no-dev',
                'yarn install && yarn build && rm -rf node_modules',
            ]);
            Arr::set($environments, 'staging.build', [
                'composer install',
                'yarn install && yarn build && rm -rf node_modules',
            ]);
        }

        return collect($environments);
    }

    /**
     * Checks if the plugin is already installed.
     */
    private function isPluginInstalled(string $projectType): bool
    {
        return ('wordpress' === $projectType && $this->wpCliExecutable->isYmirPluginInstalled())
            || ('bedrock' === $projectType && str_contains((string) file_get_contents('./composer.json'), 'ymirapp/wordpress-plugin'));
    }

    /**
     * Checks if we're able to download WordPress.
     */
    private function isWordPressDownloadable(string $projectType): bool
    {
        try {
            return in_array($projectType, ['bedrock', 'wordpress'])
                && null === $this->wpCliExecutable->getVersion()
                && ('bedrock' !== $projectType || !(new \FilesystemIterator($this->projectDirectory))->valid());
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Check if the project paths exist.
     */
    private function projectPathsExist(array $paths): bool
    {
        return $this->filesystem->exists(array_map(function (string $path) {
            return $this->projectDirectory.$path;
        }, $paths));
    }
}
