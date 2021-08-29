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

namespace Ymir\Cli;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Command\Team\SelectTeamCommand;

class CliConfiguration
{
    /**
     * The path to the configuration file.
     *
     * @var string
     */
    private $configurationFilePath;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The configuration options.
     *
     * @var Collection
     */
    private $options;

    /**
     * Constructor.
     */
    public function __construct(string $configurationFilePath, Filesystem $filesystem)
    {
        $this->configurationFilePath = $configurationFilePath;
        $this->filesystem = $filesystem;
        $this->options = $this->load($configurationFilePath);
    }

    /**
     * Save the options back to the configuration file when we're destroying the object.
     */
    public function __destruct()
    {
        $this->filesystem->dumpFile($this->configurationFilePath, (string) json_encode($this->options, JSON_PRETTY_PRINT));
    }

    /**
     * Get the access token from the global configuration file.
     */
    public function getAccessToken(): string
    {
        $token = getenv('YMIR_API_TOKEN');

        if (!is_string($token)) {
            $token = (string) $this->get('token');
        }

        return $token;
    }

    /**
     * Get the active team ID from the global configuration file.
     */
    public function getActiveTeamId(): int
    {
        if (!$this->has('active_team')) {
            throw new RuntimeException(sprintf('Please select a team using the "%s" command', SelectTeamCommand::NAME));
        }

        return (int) $this->get('active_team');
    }

    /**
     * Get the CLI version on GitHub.
     */
    public function getGitHubCliVersion(): string
    {
        return (string) $this->get('github_cli_version');
    }

    /**
     * Get the timestamp when GitHub was last checked for a CLI update.
     */
    public function getGitHubLastCheckedTimestamp(): int
    {
        return (int) $this->get('github_last_checked');
    }

    /**
     * Check if the global configuration has an access token.
     */
    public function hasAccessToken(): bool
    {
        return !empty($this->getAccessToken());
    }

    /**
     * Set the access token in the global configuration file.
     */
    public function setAccessToken(string $token)
    {
        $this->set('token', $token);
    }

    /**
     * Set the active team ID in the global configuration file.
     */
    public function setActiveTeamId(int $teamId)
    {
        $this->set('active_team', $teamId);
    }

    /**
     * Set the CLI version on GitHub.
     */
    public function setGitHubCliVersion(string $version)
    {
        $this->set('github_cli_version', $version);
    }

    /**
     * Set the timestamp when GitHub was last checked for a CLI update.
     */
    public function setGitHubLastCheckedTimestamp(int $timestamp)
    {
        $this->set('github_last_checked', $timestamp);
    }

    /**
     * Get the given configuration option or return the default.
     */
    private function get(string $option, $default = null)
    {
        return $this->options->get($option, $default);
    }

    /**
     * Checks if the configuration has the given option.
     */
    private function has(string $option): bool
    {
        return $this->options->has($option);
    }

    /**
     * Load the options from the configuration file.
     */
    private function load(string $configurationFilePath): Collection
    {
        $configuration = [];

        if ($this->filesystem->exists($configurationFilePath)) {
            $configuration = json_decode((string) file_get_contents($configurationFilePath), true);
        }

        return collect($configuration);
    }

    /**
     * Set the configuration option.
     */
    private function set(string $option, $value)
    {
        $this->options[$option] = $value;
    }
}
