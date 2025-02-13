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

namespace Ymir\Cli\Executable;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Exception\Executable\WpCliException;
use Ymir\Cli\Process\Process;

class WpCliExecutable extends AbstractExecutable
{
    /**
     * Download WordPress.
     */
    public function downloadWordPress(?string $cwd = null)
    {
        $this->run('core download', $cwd);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'WP-CLI';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        return 'wp';
    }

    /**
     * Get the WordPress version.
     */
    public function getVersion(): ?string
    {
        try {
            return trim($this->run('core version')->getOutput());
        } catch (WpCliException $exception) {
            return null;
        }
    }

    /**
     * Checks if WordPress is installed.
     */
    public function isWordPressInstalled(?string $cwd = null): bool
    {
        try {
            $this->run('core is-installed', $cwd);

            return true;
        } catch (WpCliException $exception) {
            return false;
        }
    }

    /**
     * Checks if the Ymir plugin is installed.
     */
    public function isYmirPluginInstalled(?string $cwd = null): bool
    {
        try {
            return $this->listPlugins($cwd)->contains(function (array $plugin) {
                return !empty($plugin['file']) && 1 === preg_match('/\/ymir\.php$/', $plugin['file']);
            });
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * List all the installed plugins.
     */
    public function listPlugins(?string $cwd = null): Collection
    {
        $process = $this->run('plugin list --fields=file,name,status,title,version --format=json', $cwd);

        $plugins = json_decode($process->getOutput(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Unable to get the list of installed plugins');
        }

        return collect($plugins);
    }

    /**
     * {@inheritdoc}
     */
    protected function run(string $command, ?string $cwd = null, ?float $timeout = 60): Process
    {
        if (function_exists('posix_geteuid') && 0 === posix_geteuid()) {
            throw new WpCliException('WP-CLI commands can only be run as a non-root user');
        }

        try {
            return parent::run($command, $cwd, $timeout);
        } catch (RuntimeException $exception) {
            throw new WpCliException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
