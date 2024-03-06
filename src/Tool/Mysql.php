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

use Ymir\Cli\Exception\CommandLineToolNotDetectedException;
use Ymir\Cli\Process\Process;

class Mysql
{
    /**
     * Export a MySQL database.
     */
    public static function export(string $filename, string $host, string $port, string $user, string $password, string $name)
    {
        if (!self::isMySqlDumpInstalledGlobally()) {
            throw new CommandLineToolNotDetectedException('mysqldump');
        }

        self::runCommand(sprintf('mysqldump --quick --single-transaction --skip-add-locks --default-character-set=utf8mb4 --host=%s --port=%s --user=%s --password=%s %s | gzip > %s', $host, $port, $user, $password, $name, $filename));
    }

    /**
     * Import a MySQL database.
     */
    public static function import(string $filename, string $host, string $port, string $user, string $password, string $name, bool $force = false)
    {
        if (!self::isMySqlInstalledGlobally()) {
            throw new CommandLineToolNotDetectedException('MySQL');
        }

        self::runCommand(sprintf('%s %s | mysql %s --protocol=TCP --host=%s --port=%s --user=%s --password=%s %s', str_ends_with($filename, '.sql.gz') ? 'gunzip <' : 'cat', $filename, $force ? '--force' : '', $host, $port, $user, $password, $name));
    }

    /**
     * Checks if MySQL is installed globally.
     */
    private static function isMySqlDumpInstalledGlobally(): bool
    {
        return 0 === Process::fromShellCommandline('command -v mysqldump')->run();
    }

    /**
     * Checks if MySQL is installed globally.
     */
    private static function isMySqlInstalledGlobally(): bool
    {
        return 0 === Process::fromShellCommandline('command -v mysql')->run();
    }

    /**
     * Checks if /bin/sh points to a valid default shell.
     */
    private static function isSystemDefaultShellValid(): bool
    {
        $process = Process::fromShellCommandline('ls -l /bin/sh');
        $process->run();

        return !str_contains($process->getOutput(), 'dash');
    }

    /**
     * Run the given MySQL command.
     */
    private static function runCommand(string $command): Process
    {
        $command = sprintf('set -o pipefail && %s', $command);

        if (!self::isSystemDefaultShellValid()) {
            $command = sprintf('bash -c "%s"', $command);
        }

        return Process::runShellCommandline($command, null, null);
    }
}
