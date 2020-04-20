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
use Placeholder\Cli\Command\Provider\ConnectProviderCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

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
            ->setDescription('Initialize a new project in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        if ($this->projectConfiguration->exists()
            && !$output->confirm('A project already exists in this directory. Do you want to overwrite it?', false)
        ) {
            return;
        }

        $teamId = $this->getActiveTeamId();
        $providers = $this->apiClient->getProviders($teamId);
        $projectType = $this->getProjectType();

        if (empty($projectType)
            && !$output->confirm('Couldn\'t detect a WordPress installation in this directory. Do you want to proceed?', false)
        ) {
            return;
        }

        if ($providers->isEmpty()) {
            $output->writeln('Connecting to a cloud provider');
            $this->invoke($output, ConnectProviderCommand::NAME);
            $providers = $this->apiClient->getProviders($teamId);
        }

        $name = $output->askSlug('What is the name of this project');
        $provider = 1 === count($providers)
                    ? $providers[0]['id'] :
                    $output->choiceCollection('Enter the ID of the cloud provider that the project will use', $providers);
        $region = $output->choice('Enter the name of the region that the project will be in', $this->apiClient->getRegions($provider)->all());

        $project = $this->apiClient->createProject($provider, $name, $region);

        $project['type'] = $projectType ?? 'wordpress';

        $this->projectConfiguration->createNew($project);

        $output->writeln(sprintf('"<info>%s</info>" project has been initialized in the "<info>%s</info>" region', $name, $region));
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
}
