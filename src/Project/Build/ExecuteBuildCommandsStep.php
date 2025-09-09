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

namespace Ymir\Cli\Project\Build;

use Ymir\Cli\Process\Process;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

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
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        if (!$environmentConfiguration->hasBuildConfiguration()) {
            return;
        }

        foreach ($environmentConfiguration->getBuildCommands() as $command) {
            Process::runShellCommandline($command, $this->buildDirectory, null, null, null);
        }
    }
}
