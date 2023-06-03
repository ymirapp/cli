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
use Symfony\Component\Console\Input\InputInterface;
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
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\WpCli;

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
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, ProjectConfiguration $projectConfiguration, string $projectDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
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
    protected function determineCloudProvider(string $question, InputInterface $input, OutputInterface $output): int
    {
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if ($providers->isEmpty()) {
            $output->info('Connecting to a cloud provider');

            $this->retryApi(function () use ($output) {
                $this->invoke($output, ConnectProviderCommand::NAME);
            }, 'Do you want to try to connect to a cloud provider again?', $output);
        }

        return parent::determineCloudProvider($question, $input, $output);
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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        if ($this->projectConfiguration->exists()
            && !$output->confirm('A project already exists in this directory. Do you want to overwrite it?', false)
        ) {
            return;
        } elseif ($this->projectConfiguration->exists()) {
            $this->projectConfiguration->delete();
        }

        $this->retryApi(function () use ($input, $output) {
            $projectName = $output->askSlug('What is the name of the project', basename(getcwd() ?: '') ?: null);
            $projectType = $this->determineProjectType($input, $output);
            $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider that the project will use', $input, $output);
            $region = $this->determineRegion('Enter the name of the region that the project will be in', $providerId, $input, $output);

            // Define the environments now so we check for the database server before checking for WordPress
            $environments = $this->addEnvironmentDatabaseNodes($this->getBaseEnvironmentsConfiguration($projectType), $output, $projectName, $region);

            // This needs to happen before we create the configuration file because "composer create-project"
            // needs an empty directory.
            $this->checkForWordPress($output, $projectType);

            $this->projectConfiguration->createNew($this->apiClient->createProject($providerId, $projectName, $region, $environments->keys()->all()), $environments->all(), $projectType);

            $output->infoWithDelayWarning('Project initialized');

            if (!$this->isPluginInstalled($projectType) && $output->confirm('Would you like to install the Ymir WordPress plugin?')) {
                $this->invoke($output, InstallPluginCommand::NAME);
            }

            if ($output->confirm('Will you deploy this project using a container image?', false)) {
                $this->invoke($output, CreateDockerfileCommand::NAME, ['--configure-project' => null]);
            }

            if (WpCli::isInstalledGlobally() && WpCli::isWordPressInstalled() && $output->confirm('Do you want to have Ymir scan your plugins and themes and configure your project?')) {
                $this->invoke($output, ConfigureProjectCommand::NAME);
            }
        }, 'Do you want to try creating a project again?', $output);
    }

    /**
     * Add the "database" nodes to all the environments.
     */
    private function addEnvironmentDatabaseNodes(Collection $environments, OutputInterface $output, string $projectName, string $region): Collection
    {
        $databaseServer = $this->determineDatabaseServer($output, $region);

        if (empty($databaseServer['name'])) {
            return $environments;
        }

        $databasePrefix = trim($output->askSlug('What database prefix would you like to use for this project?', $projectName));
        $environments = $environments->map(function (array $options, string $environment) use ($databasePrefix, $databaseServer) {
            Arr::set($options, 'database.server', $databaseServer['name']);
            Arr::set($options, 'database.name', $databasePrefix ? sprintf('%s_%s', rtrim($databasePrefix, '_'), $environment) : $environment);

            return $options;
        });

        if (!empty($databaseServer['publicly_accessible']) && $output->confirm(sprintf('Would you like to create the staging and production databases for your project on the "<comment>%s</comment>" database server?', $databaseServer['name']))) {
            $environments->each(function (array $options) {
                $this->invoke(new NullOutput(), CreateDatabaseCommand::NAME, ['name' => Arr::get($options, 'database.name'), '--server' => Arr::get($options, 'database.server')]);
            });
        }

        return $environments;
    }

    /**
     * Check for WordPress and offer to install it if it's not detected.
     */
    private function checkForWordPress(OutputInterface $output, string $projectType)
    {
        if (!$this->isWordPressDownloadable($projectType) || !$output->confirm('WordPress wasn\'t detected in the project directory. Would you like to download it?')) {
            return;
        }

        if ('bedrock' === $projectType) {
            $output->info('Creating new Bedrock project');
            Process::runShellCommandline('composer create-project roots/bedrock .');
        } elseif ('wordpress' === $projectType) {
            $output->info('Downloading WordPress using WP-CLI');
            WpCli::downloadWordPress();
        }

        $output->info('WordPress downloaded successfully');
    }

    /**
     * Determine the database server to use for this project.
     */
    private function determineDatabaseServer(OutputInterface $output, string $region): ?array
    {
        $database = null;
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->where('region', $region)->whereNotIn('status', ['deleting', 'failed'])->values();

        if (!$databases->isEmpty() && $output->confirm('Would you like to use an existing database server for this project?')) {
            $database = $output->choiceWithResourceDetails('Which database server would you like to use?', $databases);
        } elseif (
            (!$databases->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $output->confirm(sprintf('Your team doesn\'t have any configured database servers in the "<comment>%s</comment>" region. Would you like to create one for this team first?', $region)))
        ) {
            $this->retryApi(function () use ($output) {
                $this->invoke($output, CreateDatabaseServerCommand::NAME);
            }, 'Do you want to try creating a database server again?', $output);

            return $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->last();
        }

        return $databases->firstWhere('name', $database);
    }

    /**
     * Determine the type of project being initialized.
     */
    private function determineProjectType(InputInterface $input, OutputInterface $output): string
    {
        $type = '';

        if ($this->filesystem->exists($this->projectDirectory.'/wp-config.php')) {
            $type = 'wordpress';
        } elseif ($this->filesystem->exists(array_map(function (string $path) {
            return $this->projectDirectory.$path;
        }, ['/web/app/', '/web/wp-config.php', '/config/application.php']))) {
            $type = 'bedrock';
        }

        if (empty($type)) {
            $type = $output->choice('Please select the type of project to initialize', ['Bedrock', 'WordPress'], 'WordPress');
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
        }

        return collect($environments);
    }

    /**
     * Checks if the plugin is already installed.
     */
    private function isPluginInstalled(string $projectType): bool
    {
        return ('wordpress' === $projectType && WpCli::isYmirPluginInstalled())
            || ('bedrock' === $projectType && str_contains((string) file_get_contents('./composer.json'), 'ymirapp/wordpress-plugin'));
    }

    /**
     * Checks if we're able to download WordPress.
     */
    private function isWordPressDownloadable(string $projectType): bool
    {
        try {
            return in_array($projectType, ['bedrock', 'wordpress'])
                && !WpCli::isWordPressInstalled()
                && ('bedrock' !== $projectType || !(new \FilesystemIterator($this->projectDirectory))->valid());
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
