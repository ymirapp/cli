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

namespace Ymir\Cli\Build;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Tightenco\Collect\Support\Arr;
use Ymir\Cli\ProjectConfiguration;

class ExecuteBuildCommandsStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Executing build commands';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $environment = (array) $projectConfiguration->getEnvironment($environment);

        if (empty($environment['build'])) {
            return;
        }

        $commands = [];

        if (Arr::has($environment, 'build.commands')) {
            $commands = (array) Arr::get($environment, 'build.commands');
        } elseif (!Arr::has($environment, 'build.include')) {
            $commands = (array) $environment['build'];
        }

        foreach ($commands as $command) {
            $this->runCommand($command);
        }
    }

    /**
     * Run the build command.
     */
    private function runCommand(string $command)
    {
        $process = Process::fromShellCommandline($command, $this->buildDirectory, null, null, null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }
    }
}
