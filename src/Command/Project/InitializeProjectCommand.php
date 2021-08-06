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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Arr;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Database\CreateDatabaseCommand;
use Ymir\Cli\Command\Database\CreateDatabaseServerCommand;
use Ymir\Cli\Command\Docker\CreateDockerfileCommand;
use Ymir\Cli\Command\InstallPluginCommand;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration;
use Ymir\Cli\WpCli;

class InitializeProjectCommand extends AbstractProjectCommand
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
    protected function determineCloudProvider(string $question, InputInterface $input, ConsoleOutput $output): int
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
    protected function perform(InputInterface $input, ConsoleOutput $output)
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

            $this->projectConfiguration->createNew($this->apiClient->createProject($providerId, $projectName, $region), $environments->all(), $projectType);

            $output->infoWithDelayWarning('Project initialized');

            if (!$this->isPluginInstalled($projectType) && $output->confirm('Would you like to install the Ymir WordPress plugin?')) {
                $this->invoke($output, InstallPluginCommand::NAME);
            }

            if ($output->confirm('Will you deploy this project using a container image?', false)) {
                $this->invoke($output, CreateDockerfileCommand::NAME);

                $this->projectConfiguration->addOptionsToEnvironments([
                    'deployment' => 'image',
                ]);
            }
        }, 'Do you want to try creating a project again?', $output);
    }

    /**
     * Add the "database" nodes to all the environments.
     */
    private function addEnvironmentDatabaseNodes(Collection $environments, ConsoleOutput $output, string $projectName, string $region): Collection
    {
        $databasePrefix = '';
        $databaseServer = $this->determineDatabaseServer($output, $region);

        if (empty($databaseServer['name'])) {
            return $environments;
        } elseif (!empty($databaseServer['publicly_accessible']) && $output->confirm(sprintf('Would you like to create staging and production databases for your project on the "<comment>%s</comment>" database server?', $databaseServer['name']))) {
            $databasePrefix = $output->askSlug('What database prefix would you like to use for this project?', $projectName);
        }

        return $environments->map(function (array $options, string $environment) use ($databasePrefix, $databaseServer) {
            if (!empty($databaseServer['name']) && empty($databasePrefix)) {
                Arr::set($options, 'database', $databaseServer['name']);
            } elseif (!empty($databaseServer['name']) && !empty($databasePrefix)) {
                Arr::set($options, 'database.server', $databaseServer['name']);
                Arr::set($options, 'database.name', sprintf('%s_%s', $databasePrefix, $environment));
            }

            return $options;
        })->each(function (array $options) {
            if (!Arr::has($options, ['database.server', 'database.name'])) {
                return;
            }

            $this->invoke((new NullOutput()), CreateDatabaseCommand::NAME, ['database' => Arr::get($options, 'database.server'), 'name' => Arr::get($options, 'database.name')]);
        })->map(function (array $options) {
            if (empty($options)) {
                $options = null;
            }

            return $options;
        });
    }

    /**
     * Check for WordPress and offer to install if it's not detected.
     */
    private function checkForWordPress(ConsoleOutput $output, string $projectType)
    {
        if (!in_array($projectType, ['bedrock', 'wordpress']) || !WpCli::isInstalledGlobally() || WpCli::isWordPressInstalled() || !$output->confirm('WordPress was\'t detected in the project directory. Would you like to download it?')) {
            return;
        }

        if ('bedrock' === $projectType) {
            Process::runShellCommandline('composer create-project roots/bedrock .');
        } elseif ('wordpress' === $projectType) {
            WpCli::downloadWordPress();
        }

        $output->info('WordPress downloaded successfully');
    }

    /**
     * Determine the database server to use for this project.
     */
    private function determineDatabaseServer(ConsoleOutput $output, string $region): ?array
    {
        $database = null;
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->where('region', $region)->whereNotIn('status', ['deleting', 'failed']);

        if (!$databases->isEmpty() && $output->confirm('Would you like to use an existing database server for this project?')) {
            $database = $output->choiceWithResourceDetails('Which database server would you like to use?', $databases);
        } elseif (
            (!$databases->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $output->confirm('Your team doesn\'t have any configured database servers. Would you like to create one for this project first?'))
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
    private function determineProjectType(InputInterface $input, ConsoleOutput $output): string
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
            'production' => [],
            'staging' => ['cdn' => ['caching' => 'assets'], 'cron' => false, 'warmup' => false],
        ];

        if ('bedrock' === $projectType) {
            Arr::set($environments, 'staging.build', ['COMPOSER_MIRROR_PATH_REPOS=1 composer install']);
            Arr::set($environments, 'production.build', ['COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev']);
        }

        return collect($environments);
    }

    /**
     * Checks if the plugin is already installed.
     */
    private function isPluginInstalled(string $projectType): bool
    {
        return ('wordpress' === $projectType && WpCli::isInstalledGlobally() && WpCli::isYmirPluginInstalled())
            || ('bedrock' === $projectType && false !== strpos((string) file_get_contents('./composer.json'), 'ymirapp/wordpress-plugin'));
    }
}
