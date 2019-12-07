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
use Placeholder\Cli\Command\Provider\ConnectCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Input\InputInterface;

class InitializeCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:init';

    /**
     * The placeholder project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->projectConfiguration = $projectConfiguration;
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

        if ($providers->isEmpty()) {
            $output->writeln('Connecting to a cloud provider');
            $this->invoke($output, ConnectCommand::NAME);
            $providers = $this->apiClient->getProviders($teamId);
        }

        $name = $output->askSlug('What is the name of this project');
        $provider = 1 === count($providers)
                    ? $providers[0]['id'] :
                    $output->choiceCollection('Enter the ID of the cloud provider that the project will use', $providers);
        $region = $output->choice('Enter the name of the region that the project will be in', $this->apiClient->getRegions($provider)->all());

        $this->projectConfiguration->createNew($this->apiClient->createProject($provider, $name, $region));

        $output->writeln(sprintf('"<info>%s</info>" project has been initialized in the "<info>%s</info>" region', $name, $region));
    }
}
