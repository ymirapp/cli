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
use Tightenco\Collect\Support\Enumerable;
use Ymir\Cli\Process\Process;

class WpCli
{
    /**
     * Download WordPress.
     */
    public static function downloadWordPress(string $executable = '')
    {
        self::runCommand('core download', $executable);
    }

    /**
     * Checks if WP-CLI is installed globally.
     */
    public static function isInstalledGlobally(): bool
    {
        return 0 === Process::fromShellCommandline('command -v wp')->run();
    }

    /**
     * Checks if WordPress is installed.
     */
    public static function isWordPressInstalled(string $executable = ''): bool
    {
        try {
            self::runCommand('core is-installed', $executable);

            return true;
        } catch (RuntimeException $exception) {
            return false === stripos($exception->getMessage(), 'This does not seem to be a WordPress installation');
        }
    }

    /**
     * Checks if the Ymir plugin is installed.
     */
    public static function isYmirPluginInstalled(string $executable = ''): bool
    {
        try {
            return self::isInstalledGlobally() && self::listPlugins($executable)->contains(function (array $plugin) {
                return !empty($plugin['file']) && 1 === preg_match('/\/ymir\.php$/', $plugin['file']);
            });
        } catch (RuntimeException $exception) {
            return false;
        }
    }

    /**
     * List all the installed plugins.
     */
    public static function listPlugins(string $executable = ''): Enumerable
    {
        $process = self::runCommand('plugin list --fields=name,status,version,file --format=json', $executable);

        $plugins = collect(json_decode($process->getOutput(), true));

        if ($plugins->isEmpty()) {
            throw new RuntimeException('Unable to get the list of installed plugins');
        }

        return $plugins;
    }

    /**
     * Run WP-CLI command.
     */
    private static function runCommand(string $command, string $executable): Process
    {
        if (empty($executable) && !self::isInstalledGlobally()) {
            throw new RuntimeException('WP-CLI isn\'t available');
        } elseif (empty($executable) && self::isInstalledGlobally()) {
            $executable = 'wp';
        }

        return Process::runShellCommandline(sprintf('%s %s', $executable, $command));
    }
}
