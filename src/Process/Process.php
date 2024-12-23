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

namespace Ymir\Cli\Process;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process extends SymfonyProcess
{
    /**
     * Run a command-line in a shell wrapper.
     */
    public static function runShellCommandline(string $command, ?string $cwd = null, ?array $env = null, $input = null, ?float $timeout = 60): self
    {
        $process = self::fromShellCommandline($command, $cwd, $env, $input, $timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }

        return $process;
    }
}
