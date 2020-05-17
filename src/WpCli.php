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
use Symfony\Component\Process\Process;
use Tightenco\Collect\Support\Enumerable;

class WpCli
{
    /**
     * Checks if WP-CLI is installed globally.
     */
    public static function isInstalledGlobally(): bool
    {
        return 0 === Process::fromShellCommandline('command -v wp')->run();
    }

    /**
     * Checks if the given plugin is installed.
     */
    public static function isPluginInstalled(string $plugin, string $cwd = null, string $executable = ''): bool
    {
        return self::listPlugins($cwd, $executable)->contains('name', $plugin);
    }

    /**
     * List all the installed plugins.
     */
    public static function listPlugins(string $cwd = null, string $executable = ''): Enumerable
    {
        $process = Process::fromShellCommandline(sprintf('%s plugin list --fields=name,status,version,file --format=json', self::getExecutable($executable)), $cwd);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $plugins = collect(json_decode($process->getOutput(), true));

        if ($plugins->isEmpty()) {
            throw new RuntimeException('Unable to get the list of installed plugins');
        }

        return $plugins;
    }

    /**
     * Get the path to the WP-CLI executable.
     */
    private static function getExecutable(string $executable): string
    {
        if (empty($executable) && !self::isInstalledGlobally()) {
            throw new RuntimeException('WP-CLI isn\'t available');
        } elseif (empty($executable) && self::isInstalledGlobally()) {
            $executable = 'wp';
        }

        return $executable;
    }
}
