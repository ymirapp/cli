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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Database\CreateDatabaseCommand;
use Ymir\Cli\Command\Database\CreateDatabaseServerCommand;
use Ymir\Cli\Command\InstallPluginCommand;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Console\ConsoleOutput;
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
            ->setDescription('Creates a new project in the current directory')
            ->setAliases([self::ALIAS])
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the project')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the project will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the project will be located')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The type of project being created');
    }

    /**
     * {@inheritdoc}
     */
    protected function determineCloudProvider(string $question, InputInterface $input, ConsoleOutput $output): int
    {
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if ($providers->isEmpty()) {
            $output->info('Connecting to a cloud provider');
            $this->invoke($output, ConnectProviderCommand::NAME);
        }

        return parent::determineCloudProvider($question, $input, $output);
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
        }

        $databaseName = '';
        $projectName = $this->determineProjectName($input, $output);
        $projectType = $this->determineProjectType($input, $output);
        $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider that the project will use', $input, $output);
        $region = $this->determineRegion('Enter the name of the region that the project will be in', $providerId, $input, $output);

        $databaseServer = $this->determineDatabaseServer($output, $region);

        if (!empty($databaseServer)) {
            $databaseName = $this->determineDatabaseName($databaseServer, $projectName, $output);
        }

        $this->projectConfiguration->createNew($this->apiClient->createProject($providerId, $projectName, $region), $databaseName, $databaseServer['name'] ?? '', $projectType);

        $output->infoWithDelayWarning('Project initialized');

        if (!$this->isPluginInstalled($projectType) && $output->confirm('Would you like to install the Ymir WordPress plugin?')) {
            $this->invoke($output, InstallPluginCommand::NAME);
        }
    }

    /**
     * Determine the name of the database to use for this project.
     */
    private function determineDatabaseName(array $databaseServer, string $projectName, ConsoleOutput $output): string
    {
        $databaseName = '';
        $databaseServer = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->firstWhere('name', $databaseServer);

        if (!isset($databaseServer['name'], $databaseServer['status'])
            || 'available' !== $databaseServer['status']
            || !$output->confirm(sprintf('Would you like to create a new database for your project on the "<comment>%s</comment>" database server? Otherwise, the default "wordpress" database will be used.', $databaseServer['name']))
        ) {
            return $databaseName;
        }

        $databaseName = $output->askSlug('What is the name of the new database that you would like to create for this project', $projectName);

        $this->invoke($output, CreateDatabaseCommand::NAME, ['database' => $databaseServer['name'], 'name' => $databaseName]);

        return $databaseName;
    }

    /**
     * Determine the database server to use for this project.
     */
    private function determineDatabaseServer(ConsoleOutput $output, string $region): ?array
    {
        $database = null;
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->where('region', $region)->whereNotIn('status', 'deleting');

        if (!$databases->isEmpty() && $output->confirm('Would you like to use an existing database server for this project?')) {
            $database = (string) $output->choiceWithResourceDetails('Which database server would you like to use?', $databases);
        } elseif (
            (!$databases->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $output->confirm('Your team doesn\'t have any configured database servers. Would you like to create one for this project first?'))
        ) {
            $this->invoke($output, CreateDatabaseServerCommand::NAME);

            return $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->last();
        }

        return $databases->firstWhere('name', $database);
    }

    /**
     * Determine the name of the project.
     */
    private function determineProjectName(InputInterface $input, ConsoleOutput $output): string
    {
        $name = $this->getStringOption($input, 'name', true);

        if (empty($name) && $input->isInteractive()) {
            $name = $output->askSlug('What is the name of the project', basename(getcwd() ?: '') ?: null);
        }

        return (string) $name;
    }

    /**
     * Determine the type of project being initialized.
     */
    private function determineProjectType(InputInterface $input, ConsoleOutput $output): string
    {
        $type = $this->getStringOption($input, 'type');

        if ($this->filesystem->exists($this->projectDirectory.'/wp-config.php')) {
            $type = 'wordpress';
        } elseif ($this->filesystem->exists(array_map(function (string $path) {
            return $this->projectDirectory.$path;
        }, ['/web/app/', '/web/wp-config.php', '/config/application.php']))) {
            $type = 'bedrock';
        }

        if (empty($type)) {
            $type = $output->choice('Please select the type of project to initialize', ['Bedrock', 'WordPress'], 'wordpress');
        }

        return strtolower($type);
    }

    /**
     * Checks if the plugin is already installed.
     */
    private function isPluginInstalled(string $projectType): bool
    {
        return ('wordpress' === $projectType && WpCli::isInstalledGlobally() && WpCli::isPluginInstalled('ymir'))
            || ('bedrock' === $projectType && false !== strpos((string) file_get_contents('./composer.json'), 'ymirapp/wordpress-plugin'));
    }
}
