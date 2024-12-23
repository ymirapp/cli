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

use Ymir\Cli\Exception\Executable\ExecutableNotDetectedException;
use Ymir\Cli\Process\Process;

abstract class AbstractExecutable implements ExecutableInterface
{
    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        return $this->isExecutableInstalled($this->getExecutable());
    }

    /**
     * Get an unstarted Process object to run the executable with the given command.
     */
    protected function getProcess(string $command, ?string $cwd = null, ?float $timeout = 60): Process
    {
        if (!$this->isInstalled()) {
            throw new ExecutableNotDetectedException($this);
        }

        return Process::fromShellCommandline(sprintf('%s %s', $this->getExecutable(), $command), $cwd, null, null, $timeout);
    }

    /**
     * Check if the given executable is installed.
     */
    protected function isExecutableInstalled(string $executable): bool
    {
        return 0 === Process::fromShellCommandline(sprintf('which %s', $executable))->run();
    }

    /**
     * Run the executable with the given command and return the Process object used to run it.
     */
    protected function run(string $command, ?string $cwd = null, ?float $timeout = 60): Process
    {
        if (!$this->isInstalled()) {
            throw new ExecutableNotDetectedException($this);
        }

        return Process::runShellCommandline(sprintf('%s %s', $this->getExecutable(), $command), $cwd, null, null, $timeout);
    }
}
