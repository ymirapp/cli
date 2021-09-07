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
use Ymir\Cli\Docker;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

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
    public function isNeeded(string $environment, ProjectConfiguration $projectConfiguration): bool
    {
        return 'image' === Arr::get($projectConfiguration->getEnvironment($environment), 'deployment');
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $file = 'Dockerfile';

        if ($this->filesystem->exists($this->buildDirectory.sprintf('/.%s.Dockerfile', $environment))) {
            $file = sprintf('.%s.Dockerfile', $environment);
        } elseif ($this->filesystem->exists($this->buildDirectory.sprintf('/%s.Dockerfile', $environment))) {
            $file = sprintf('%s.Dockerfile', $environment);
        }

        if (!$this->filesystem->exists($this->buildDirectory.'/'.$file)) {
            throw new RuntimeException('Unable to find a Dockerfile to build the container image');
        }

        Docker::build($file, sprintf('%s:%s', $projectConfiguration->getProjectName(), $environment), $this->buildDirectory);
    }
}
