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

namespace Placeholder\Cli\Command;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Command\Team\SelectTeamCommand;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * The API client that interacts with the placeholder API.
     *
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * The global placeholder CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration)
    {
        parent::__construct();

        $this->apiClient = $apiClient;
        $this->cliConfiguration = $cliConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (LoginCommand::NAME !== $this->getName() && !$this->apiClient->isAuthenticated()) {
            throw new RuntimeException(sprintf('Please authenticate using the "%s" command before using this command', LoginCommand::NAME));
        }

        $this->perform($input, new OutputStyle($input, $output));
    }

    /**
     * Get the active team ID from the global configuration file.
     */
    protected function getActiveTeamId(): int
    {
        if (!$this->cliConfiguration->has('active_team')) {
            throw new RuntimeException(sprintf('Please select a team using the "%s" command', SelectTeamCommand::NAME));
        }

        return (int) $this->cliConfiguration->get('active_team');
    }

    /**
     * Get the value of a option that should be boolean.
     */
    protected function getBooleanOption(InputInterface $input, string $option): bool
    {
        return $input->hasOption($option) && $input->getOption($option);
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
        } elseif (!is_numeric($value)) {
            throw new RuntimeException(sprintf('The "--%s" option must be a numeric value', $option));
        }

        return (int) $value;
    }

    /**
     * Get the value of a option that should be a string. Returns null if not present.
     */
    protected function getStringOption(InputInterface $input, string $option): ?string
    {
        $value = null;

        if ($input->hasOption($option)) {
            $value = $input->getOption($option);
        }

        if (null === $value) {
            return $value;
        } elseif (!is_string($value)) {
            throw new RuntimeException(sprintf('The "--%s" option must be a string value', $option));
        }

        return $value;
    }

    /**
     * Invoke another console command.
     */
    protected function invoke(OutputStyle $output, string $command, array $arguments = []): int
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new RuntimeException('No Application instance found');
        }

        return $application->find($command)->run(new ArrayInput($arguments), $output);
    }

    /**
     * Perform the command.
     */
    abstract protected function perform(InputInterface $input, OutputStyle $output);

    /**
     * Set the access token in the global configuration file.
     */
    protected function setAccessToken(string $token)
    {
        $this->cliConfiguration->set('token', $token);
    }

    /**
     * Set the active team ID in the global configuration file.
     */
    protected function setActiveTeamId(int $teamId)
    {
        $this->cliConfiguration->set('active_team', $teamId);
    }
}
