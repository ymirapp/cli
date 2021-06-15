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
use Ymir\Cli\Process\Process;

class Docker
{
    /**
     * Build a docker image.
     */
    public static function build(string $file, string $tag, ?string $cwd = null)
    {
        self::runCommand(sprintf('build --pull --file=%s --tag=%s .', $file, $tag), $cwd);
    }

    /**
     * Login to a Docker registry.
     */
    public static function login(string $username, string $password, string $server, ?string $cwd = null)
    {
        self::runCommand(sprintf('login --username %s --password %s %s', $username, $password, $server), $cwd);
    }

    /**
     * Push a docker image.
     */
    public static function push(string $image, ?string $cwd = null)
    {
        self::runCommand(sprintf('push %s', $image), $cwd);
    }

    /**
     * Create a docker image tag.
     */
    public static function tag(string $sourceImage, string $targetImage, ?string $cwd = null)
    {
        self::runCommand(sprintf('tag %s %s', $sourceImage, $targetImage), $cwd);
    }

    /**
     * Run Docker command.
     */
    private static function runCommand(string $command, ?string $cwd = null): Process
    {
        if (0 !== Process::fromShellCommandline('command -v docker')->run()) {
            throw new RuntimeException('Docker isn\'t available');
        }

        return Process::runShellCommandline(sprintf('docker %s', $command), $cwd, null);
    }
}
