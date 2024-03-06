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
use Ymir\Cli\Exception\CommandLineToolNotDetectedException;
use Ymir\Cli\Process\Process;

class CommandLineTool
{
    /**
     * Checks if command-line tool is installed globally.
     */
    public static function isInstalledGlobally(): bool
    {
        return 0 === Process::fromShellCommandline(sprintf('which %s', static::getCommand()))->run();
    }

    /**
     * Get the command to interact with the command-line tool.
     */
    protected static function getCommand(): string
    {
        throw new RuntimeException('Must override "getCommand" method');
    }

    /**
     * Get the name of the command-line tool.
     */
    protected static function getName(): string
    {
        throw new RuntimeException('Must override "getName" method');
    }

    /**
     * Get a Process object for the given command.
     */
    protected static function getProcess(string $command, ?string $cwd = null, ?float $timeout = 60): Process
    {
        if (!static::isInstalledGlobally()) {
            throw new CommandLineToolNotDetectedException(static::getName());
        }

        return Process::fromShellCommandline(sprintf('%s %s', static::getCommand(), $command), $cwd, null, null, $timeout);
    }

    /**
     * Run command.
     */
    protected static function runCommand(string $command, ?string $cwd = null): Process
    {
        if (!static::isInstalledGlobally()) {
            throw new CommandLineToolNotDetectedException(static::getName());
        }

        return Process::runShellCommandline(sprintf('%s %s', static::getCommand(), $command), $cwd, null);
    }
}
