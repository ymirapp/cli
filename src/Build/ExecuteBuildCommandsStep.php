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

use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

class ExecuteBuildCommandsStep extends AbstractBuildStep
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
        $environment = $projectConfiguration->getEnvironment($environment);

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
            Process::runShellCommandline($command, $this->buildDirectory, null, null, null);
        }
    }
}
