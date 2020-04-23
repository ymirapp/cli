<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Project;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Command\Database\CreateDatabaseCommand;
use Placeholder\Cli\Command\Provider\ConnectProviderCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Collection;

class InitializeProjectCommand extends AbstractCommand
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
     * The placeholder project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

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
        parent::__construct($apiClient, $cliConfiguration);

        $this->filesystem = $filesystem;
        $this->projectConfiguration = $projectConfiguration;
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
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The database used by the project');
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

        $providers = $this->getProviders($output);

        $name = $output->askSlug('What is the name of the project');
        $provider = 1 === count($providers)
                    ? $providers[0]['id'] :
                    $output->choiceCollection('Enter the ID of the cloud provider that the project will use', $providers);
        $region = $output->choice('Enter the name of the region that the project will be in', $this->apiClient->getRegions($provider)->all());

        if (empty($databaseName)) {
            $databaseName = $this->determineDatabaseName($output);
        }

        $project = $this->apiClient->createProject($provider, $name, $region);

        $this->projectConfiguration->createNew($project, $databaseName, $projectType);

        $output->infoWithDelayWarning('Project initialized');
    }

    /**
     * Determine the database to use for this project.
     */
    private function determineDatabaseName(OutputStyle $output): string
    {
        $databaseName = '';
        $databases = $this->apiClient->getDatabases($this->getActiveTeamId());

        if (!$databases->isEmpty() && $output->confirm('Would you like to use an existing database for this project?')) {
            $databaseName = (string) $output->choice('Which database would you like to use?', $databases->pluck('name')->all());
        } elseif (
            (!$databases->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databases->isEmpty() && $output->confirm('Your team doesn\'t have any configured databases. Would you like to create one for this project first?'))
        ) {
            $this->invoke($output, CreateDatabaseCommand::NAME);
            $databaseName = (string) $this->apiClient->getDatabases($this->getActiveTeamId())->pluck('name')->last();
        }

        return $databaseName;
    }

    /**
     * Get the database name from the console input.
     */
    private function getDatabaseName(InputInterface $input): string
    {
        $databaseName = $input->getOption('database') ?? '';

        if (!is_string($databaseName)) {
            throw new RuntimeException('Invalid "database" option given');
        } elseif (empty($databaseName)) {
            return $databaseName;
        }

        $databases = $this->apiClient->getDatabases($this->getActiveTeamId());

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
     * Get the cloud providers.
     */
    private function getProviders(OutputStyle $output): Collection
    {
        $providers = $this->apiClient->getProviders($this->getActiveTeamId());

        if ($providers->isEmpty()) {
            $output->info('Connecting to a cloud provider');
            $this->invoke($output, ConnectProviderCommand::NAME);
            $providers = $this->apiClient->getProviders($this->getActiveTeamId());
        }

        return $providers;
    }
}
