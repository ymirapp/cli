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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Database\CreateDatabaseCommand;
use Ymir\Cli\Command\InstallPluginCommand;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\ProjectConfiguration;
use Ymir\Cli\WpCli;

class InitializeProjectCommand extends AbstractProjectCommand
{
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
            ->setAliases(['init'])
            ->setDescription('Creates a new project in the current directory')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The database used by the project')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the project');
    }

    /**
     * {@inheritdoc}
     */
    protected function determineCloudProvider(InputInterface $input, OutputStyle $output, string $question): int
    {
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if ($providers->isEmpty()) {
            $output->info('Connecting to a cloud provider');
            $this->invoke($output, ConnectProviderCommand::NAME);
        }

        return parent::determineCloudProvider($input, $output, $question);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $databaseName = $this->getDatabaseName($input);

        if ($this->projectConfiguration->exists()
            && !$output->confirm('A project already exists in this directory. Do you want to overwrite it?', false)
        ) {
            return;
        }

        $projectType = $this->getProjectType();

        if (empty($projectType)
            && !$output->confirm('No WordPress installation detected in this directory. Do you want to proceed?', false)) {
            return;
        }

        $name = $this->determineName($input, $output);
        $providerId = $this->determineCloudProvider($input, $output, 'Enter the ID of the cloud provider that the project will use');
        $region = $this->determineRegion($input, $output, $providerId, 'Enter the name of the region that the project will be in');

        if (empty($databaseName)) {
            $databaseName = $this->determineDatabaseName($output);
        }

        $project = $this->apiClient->createProject($providerId, $name, $region);

        $this->projectConfiguration->createNew($project, $databaseName, $projectType);

        $output->infoWithDelayWarning('Project initialized');

        if (!$this->isPluginInstalled($projectType) && $output->confirm('Would you like to install the Ymir WordPress plugin?')) {
            $this->invoke($output, InstallPluginCommand::NAME);
        }
    }

    /**
     * Determine the database to use for this project.
     */
    private function determineDatabaseName(OutputStyle $output): string
    {
        $databaseName = '';
        $databases = $this->apiClient->getDatabases($this->cliConfiguration->getActiveTeamId());

        if (!$databases->isEmpty() && $output->confirm('Would you like to use an existing database for this project?')) {
            $databaseName = (string) $output->choice('Which database would you like to use?', $databases->pluck('name')->all());
        } elseif (
            (!$databases->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $output->confirm('Your team doesn\'t have any configured databases. Would you like to create one for this project first?'))
        ) {
            $this->invoke($output, CreateDatabaseCommand::NAME);
            $databaseName = (string) $this->apiClient->getDatabases($this->cliConfiguration->getActiveTeamId())->pluck('name')->last();
        }

        return $databaseName;
    }

    /**
     * Determine the name of the project.
     */
    private function determineName(InputInterface $input, OutputStyle $output): string
    {
        $name = $this->getStringOption($input, 'name');

        if (empty($name) && !$input->isInteractive()) {
            throw new InvalidArgumentException('You must use the "--name" option when running in non-interactive mode');
        } elseif (empty($name) && $input->isInteractive()) {
            $name = $output->askSlug('What is the name of the project');
        }

        return (string) $name;
    }

    /**
     * Get the database name from the console input.
     */
    private function getDatabaseName(InputInterface $input): ?string
    {
        $databaseName = $this->getStringOption($input, 'database');

        if (empty($databaseName)) {
            return $databaseName;
        }

        $databases = $this->apiClient->getDatabases($this->cliConfiguration->getActiveTeamId());

        if (!$databases->contains(function (array $database) use ($databaseName) { return isset($database['name']) && $database['name'] === $databaseName; })) {
            throw new RuntimeException(sprintf('There is no "%s" database on your current team', $databaseName));
        }

        return $databaseName;
    }

    /**
     * Get the project type that we're initializing.
     */
    private function getProjectType(): string
    {
        $type = '';

        if ($this->filesystem->exists($this->projectDirectory.'/wp-config.php')) {
            $type = 'wordpress';
        } elseif ($this->filesystem->exists($this->projectDirectory.'/composer.json')
            && false !== strpos((string) file_get_contents($this->projectDirectory.'/composer.json'), 'roots/wordpress')
        ) {
            $type = 'bedrock';
        }

        return $type;
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
