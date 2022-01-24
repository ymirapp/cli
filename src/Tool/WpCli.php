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

namespace Ymir\Cli\Tool;

use Symfony\Component\Console\Exception\RuntimeException;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Exception\WpCliException;
use Ymir\Cli\Process\Process;

class WpCli extends CommandLineTool
{
    /**
     * Download WordPress.
     */
    public static function downloadWordPress()
    {
        self::runCommand('core download');
    }

    /**
     * Checks if WordPress is installed.
     */
    public static function isWordPressInstalled(): bool
    {
        try {
            self::runCommand('core is-installed');

            return true;
        } catch (WpCliException $exception) {
            return false;
        }
    }

    /**
     * Checks if the Ymir plugin is installed.
     */
    public static function isYmirPluginInstalled(): bool
    {
        try {
            return self::listPlugins()->contains(function (array $plugin) {
                return !empty($plugin['file']) && 1 === preg_match('/\/ymir\.php$/', $plugin['file']);
            });
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * List all the installed plugins.
     */
    public static function listPlugins(): Collection
    {
        $process = self::runCommand('plugin list --fields=file,name,status,title,version --format=json');

        $plugins = collect(json_decode($process->getOutput(), true));

        if ($plugins->isEmpty()) {
            throw new RuntimeException('Unable to get the list of installed plugins');
        }

        return $plugins;
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommand(): string
    {
        return 'wp';
    }

    /**
     * {@inheritdoc}
     */
    protected static function getName(): string
    {
        return 'WP-CLI';
    }

    /**
     * {@inheritdoc}
     */
    protected static function runCommand(string $command, ?string $cwd = null): Process
    {
        if (function_exists('posix_geteuid') && 0 === posix_geteuid()) {
            throw new RuntimeException('WP-CLI commands can only be run as a non-root user');
        }

        try {
            return parent::runCommand($command, $cwd);
        } catch (RuntimeException $exception) {
            throw new WpCliException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
