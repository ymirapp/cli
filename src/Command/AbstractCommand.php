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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\Exception\ApiClientException;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\ProjectConfiguration;

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
        parent::__construct();

        $this->apiClient = $apiClient;
        $this->cliConfiguration = $cliConfiguration;
        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * Determine the cloud provider to use.
     */
    protected function determineCloudProvider(string $question, InputInterface $input, ConsoleOutput $output): int
    {
        $providerId = $this->getStringOption($input, 'provider', true);
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        if (is_numeric($providerId) && $providers->contains('id', $providerId)) {
            return (int) $providerId;
        } elseif (is_numeric($providerId) && $providers->contains('id', $providerId)) {
            throw new InvalidArgumentException('The given "provider" isn\'t available to currently active team');
        }

        if ($this->projectConfiguration->exists()) {
            $providers = collect([$this->apiClient->getProject($this->projectConfiguration->getProjectId())->get('provider')]);
        }

        return 1 === count($providers) ? $providers[0]['id'] : $output->choiceWithId($question, $providers);
    }

    /**
     * Determine the cloud provider region to use.
     */
    protected function determineRegion(string $question, int $providerId, InputInterface $input, ConsoleOutput $output): string
    {
        $region = $this->getStringOption($input, 'region', true);
        $regions = $this->apiClient->getRegions($providerId);

        if ($regions->isEmpty()) {
            throw new RuntimeException('The Ymir API failed to return the cloud provider regions');
        } elseif (!empty($region) && $regions->contains($region)) {
            return $region;
        } elseif (!empty($region) && !$regions->contains($region)) {
            throw new InvalidArgumentException('The given "region" isn\'t a valid cloud provider region');
        }

        return $this->projectConfiguration->exists() ? $this->apiClient->getProject($this->projectConfiguration->getProjectId())->get('region') : (string) $output->choice($question, $regions->all());
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

        return $this->perform($input, new ConsoleOutput($input, $output)) ?? 0;
    }

    /**
     * Get the value of an argument that should be an array.
     */
    protected function getArrayArgument(InputInterface $input, string $argument, bool $requiredNonInteractive = true): array
    {
        $value = $input->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$input->isInteractive()) {
            throw new InvalidArgumentException(sprintf('You must pass a "%s" argument when running in non-interactive mode', $argument));
        } elseif (null !== $value && !is_array($value)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument must be an array value', $argument));
        }

        return (array) $value;
    }

    /**
     * Get the value of a option that should be an array.
     */
    protected function getArrayOption(InputInterface $input, string $option): array
    {
        $value = [];

        if ($input->hasOption($option)) {
            $value = $input->getOption($option);
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option must be an array', $option));
        }

        return $value;
    }

    /**
     * Get the value of a option that should be boolean.
     */
    protected function getBooleanOption(InputInterface $input, string $option): bool
    {
        return $input->hasOption($option) && $input->getOption($option);
    }

    /**
     * Get the value of an argument that should be numeric.
     */
    protected function getNumericArgument(InputInterface $input, string $argument, bool $requiredNonInteractive = true): int
    {
        $value = $input->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$input->isInteractive()) {
            throw new InvalidArgumentException(sprintf('You must pass a "%s" argument when running in non-interactive mode', $argument));
        } elseif (null !== $value && !is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument must be a numeric value', $argument));
        }

        return (int) $value;
    }

    /**
     * Get the value of a option that should be numeric. Returns null if not present.
     */
    protected function getNumericOption(InputInterface $input, string $option): ?int
    {
        $value = null;

        if ($input->hasOption($option)) {
            $value = $input->getOption($option);
        }

        if (null === $value) {
            return $value;
        } elseif (is_array($value) || !is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option must be a numeric value', $option));
        }

        return (int) $value;
    }

    /**
     * Get the value of an argument that should be a string.
     */
    protected function getStringArgument(InputInterface $input, string $argument, bool $requiredNonInteractive = true): string
    {
        $value = $input->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$input->isInteractive()) {
            throw new InvalidArgumentException(sprintf('You must pass a "%s" argument when running in non-interactive mode', $argument));
        } elseif (null !== $value && !is_string($value)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument must be a string value', $argument));
        }

        return (string) $value;
    }

    /**
     * Get the value of a option that should be a string. Returns null if not present.
     */
    protected function getStringOption(InputInterface $input, string $option, bool $requiredNonInteractive = false): ?string
    {
        $value = null;

        if ($input->hasOption($option)) {
            $value = $input->getOption($option);
        }

        if (null === $value && $requiredNonInteractive && !$input->isInteractive()) {
            throw new InvalidArgumentException(sprintf('You must use the "--%s" option when running in non-interactive mode', $option));
        } elseif (null !== $value && !is_string($value)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option must be a string value', $option));
        }

        return $value;
    }

    /**
     * Invoke another console command.
     */
    protected function invoke(OutputInterface $output, string $command, array $arguments = []): int
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new RuntimeException('No Application instance found');
        }

        return $application->find($command)->run(new ArrayInput($arguments), $output);
    }

    /**
     * Whether the command must always be run in interactive mode or not.
     */
    protected function mustBeInteractive(): bool
    {
        return false;
    }

    /**
     * Perform the command.
     */
    abstract protected function perform(InputInterface $input, ConsoleOutput $output);

    /**
     * Retry an API operation.
     */
    protected function retryApi(callable $callable, string $message, ConsoleOutput $output)
    {
        while (true) {
            try {
                return $callable();
            } catch (ApiClientException $exception) {
                $output->newLine();
                $output->exception($exception);

                if (!$output->confirm($message)) {
                    throw new CommandCancelledException();
                }
            }
        }
    }

    /**
     * Wait for the given callable to complete.
     */
    protected function wait(callable $callable, $maxAttempts = 20, $sleep = 1)
    {
        $attempts = 0;

        do {
            $result = $callable();
            sleep($sleep);
            ++$attempts;
        } while (empty($result) && $attempts < $maxAttempts);

        return $result;
    }
}
