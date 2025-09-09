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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

class BuildContainerImageStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The Docker executable.
     *
     * @var DockerExecutable
     */
    private $dockerExecutable;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, DockerExecutable $dockerExecutable, Filesystem $filesystem)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->dockerExecutable = $dockerExecutable;
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
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        $dockerfileName = 'Dockerfile';

        if ($this->filesystem->exists($this->buildDirectory.'/'.$environmentConfiguration->getName().'.'.$dockerfileName)) {
            $dockerfileName = $environmentConfiguration->getName().'.'.$dockerfileName;
        }

        if (!$this->filesystem->exists($this->buildDirectory.'/'.$dockerfileName)) {
            throw new BuildFailedException('Unable to find a "Dockerfile" to build the container image');
        }

        $this->dockerExecutable->build($dockerfileName, sprintf('%s:%s', $projectConfiguration->getProjectName(), $environmentConfiguration->getName()), $environmentConfiguration->getArchitecture(), $this->buildDirectory);
    }
}
