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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\RuntimeException;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\Docker;

class BuildContainerImageStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Building container image';
    }

    /**
     * {@inheritdoc}
     */
    public function isNeeded(array $buildOptions, ProjectConfiguration $projectConfiguration): bool
    {
        return 'image' === Arr::get($projectConfiguration->getEnvironment($buildOptions['environment']), 'deployment');
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $dockerfileName = 'Dockerfile';

        if ($this->filesystem->exists($this->buildDirectory.'/'.$environment.'.'.$dockerfileName)) {
            $dockerfileName = $environment.'.'.$dockerfileName;
        }

        if (!$this->filesystem->exists($this->buildDirectory.'/'.$dockerfileName)) {
            throw new RuntimeException('Unable to find a "Dockerfile" to build the container image');
        }

        Docker::build($dockerfileName, sprintf('%s:%s', $projectConfiguration->getProjectName(), $environment), $this->buildDirectory);
    }
}
