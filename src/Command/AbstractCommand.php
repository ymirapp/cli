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

namespace Ymir\Cli\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Network\CreateNetworkCommand;
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Console\HiddenInputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\InputDefinition;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;
use Ymir\Sdk\Exception\ClientException;

abstract class AbstractCommand extends Command
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    protected $cliConfiguration;

    /**
     * The console input.
     *
     * @var Input
     */
    protected $input;

    /**
     * The console output.
     *
     * @var Output
     */
    protected $output;

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        $this->apiClient = $apiClient;
        $this->cliConfiguration = $cliConfiguration;
        $this->projectConfiguration = $projectConfiguration;

        $this->setDefinition(new InputDefinition());

        $this->configure();
    }

    /**
     * Adds an option that doesn't appear in the "help" command.
     */
    public function addHiddenOption($name, $shortcut = null, $mode = null, $default = null)
    {
        $this->getDefinition()->addOption(new HiddenInputOption($name, $shortcut, $mode, $default));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->input = new Input($input);
        $this->output = new Output($input, $output);

        return parent::run($input, $output);
    }

    /**
     * Determine the cloud provider to use.
     */
    protected function determineCloudProvider(string $question): int
    {
        $providerId = $this->input->getStringOption('provider', true);
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if (is_numeric($providerId) && $providers->contains('id', $providerId)) {
            return (int) $providerId;
        } elseif (is_numeric($providerId) && $providers->contains('id', $providerId)) {
            throw new InvalidInputException('The given "provider" isn\'t available to currently active team');
        }

        if ($this->projectConfiguration->exists()) {
            $providers = collect([$this->apiClient->getProject($this->projectConfiguration->getProjectId())->get('provider')]);
        }

        if ($providers->isEmpty()) {
            throw new RuntimeException(sprintf('There are no cloud providers connected to currently active team. You can connect to one using the "%s" command.', ConnectProviderCommand::NAME));
        }

        return 1 === count($providers) ? $providers[0]['id'] : $this->output->choiceWithId($question, $providers);
    }

    /**
     * Determine the network to use.
     */
    protected function determineNetwork(string $question): int
    {
        $networkIdOrName = null;

        if ($this->input->hasArgument('network')) {
            $networkIdOrName = $this->input->getStringArgument('network');
        } elseif ($this->input->hasOption('network')) {
            $networkIdOrName = $this->input->getStringOption('network', true);
        }

        $networks = $this->apiClient->getNetworks($this->cliConfiguration->getActiveTeamId());

        if (empty($networkIdOrName)) {
            $networkIdOrName = $this->output->choiceWithResourceDetails($question, $networks);
        } elseif (1 < $networks->where('name', $networkIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a network because more than one network has the name "%s"', $networkIdOrName));
        }

        $network = $networks->firstWhere('name', $networkIdOrName) ?? $networks->firstWhere('id', $networkIdOrName);

        if (empty($network['id'])) {
            throw new InvalidInputException(sprintf('Unable to find a network with "%s" as the ID or name', $networkIdOrName));
        }

        return (int) $network['id'];
    }

    /**
     * Determine the network to use or create one otherwise.
     */
    protected function determineOrCreateNetwork(string $question): int
    {
        $networks = $this->apiClient->getNetworks($this->cliConfiguration->getActiveTeamId())->whereNotIn('status', ['deleting', 'failed']);

        if ($networks->isEmpty() && !$this->output->confirm('Your team doesn\'t have any provisioned networks. Would you like to create one first? <fg=default>(Answering "<comment>no</comment>" will cancel the command.)</>')) {
            throw new CommandCancelledException();
        }

        if ($networks->isEmpty()) {
            $this->retryApi(function () {
                $this->invoke(CreateNetworkCommand::NAME);
            }, 'Do you want to try creating a network again?');

            return (int) Arr::get($this->apiClient->getNetworks($this->cliConfiguration->getActiveTeamId())->last(), 'id');
        }

        return $this->determineNetwork($question);
    }

    /**
     * Determine the project to use.
     */
    protected function determineProject(string $question): int
    {
        $projects = $this->apiClient->getProjects($this->cliConfiguration->getActiveTeamId());

        if ($projects->isEmpty()) {
            throw new RuntimeException('There are no projects on the currently active team.');
        }

        $projectIdOrName = $this->input->getStringArgument('project');

        if (empty($projectIdOrName) && $this->projectConfiguration->exists()) {
            $projectIdOrName = $this->projectConfiguration->getProjectId();
        } elseif (empty($projectIdOrName)) {
            $projectIdOrName = $this->output->choiceWithId($question, $projects);
        }

        if (1 < $projects->where('name', $projectIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a project because more than one project has the name "%s"', $projectIdOrName));
        }

        $project = $projects->firstWhere('name', $projectIdOrName) ?? $projects->firstWhere('id', $projectIdOrName);

        if (empty($project['id'])) {
            throw new InvalidInputException(sprintf('Unable to find a project with "%s" as the ID or name', $projectIdOrName));
        }

        return (int) $project['id'];
    }

    /**
     * Determine the cloud provider region to use.
     */
    protected function determineRegion(string $question, int $providerId): string
    {
        $region = $this->input->getStringOption('region', true);
        $regions = $this->apiClient->getRegions($providerId);

        if ($regions->isEmpty()) {
            throw new RuntimeException('The Ymir API failed to return the cloud provider regions');
        } elseif (!empty($region) && $regions->keys()->contains(strtolower($region))) {
            return $region;
        } elseif (!empty($region) && !$regions->keys()->contains(strtolower($region))) {
            throw new InvalidInputException('The given "region" isn\'t a valid cloud provider region');
        }

        return $this->projectConfiguration->exists() ? $this->apiClient->getProject($this->projectConfiguration->getProjectId())->get('region') : (string) $this->output->choice($question, $regions);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive() && $this->mustBeInteractive()) {
            throw new RuntimeException(sprintf('Cannot run "%s" command in non-interactive mode', $input->getFirstArgument()));
        } elseif (LoginCommand::NAME !== $this->getName() && !$this->apiClient->isAuthenticated()) {
            throw new RuntimeException(sprintf('Please authenticate using the "%s" command before using this command', LoginCommand::NAME));
        }

        return $this->perform() ?? Command::SUCCESS;
    }

    /**
     * Invoke another console command.
     */
    protected function invoke(string $command, array $arguments = [], ?OutputInterface $output = null): int
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new RuntimeException('No Application instance found');
        }

        return $application->find($command)->run(new ArrayInput($arguments), $output ?? $this->output);
    }

    /**
     * Whether the command must always be run in interactive mode or not.
     */
    protected function mustBeInteractive(): bool
    {
        return false;
    }

    /**
     * Retry an API operation.
     */
    protected function retryApi(callable $callable, string $message)
    {
        while (true) {
            try {
                return $callable();
            } catch (ClientException $exception) {
                $this->output->newLine();
                $this->output->exception($exception);

                if (!$this->output->confirm($message)) {
                    throw new CommandCancelledException();
                }
            }
        }
    }

    /**
     * Wait for the given callable to complete.
     */
    protected function wait(callable $callable, int $timeout = 60, int $sleep = 1)
    {
        if (0 !== $timeout) {
            $timeout += time();
        }

        do {
            $result = $callable();
            sleep($sleep);
        } while (empty($result) && time() < $timeout);

        return $result;
    }

    /**
     * Perform the command.
     */
    abstract protected function perform();
}
