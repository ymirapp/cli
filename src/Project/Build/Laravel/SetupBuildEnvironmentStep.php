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

namespace Ymir\Cli\Project\Build\Laravel;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Build\BuildStepInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\LaravelProjectType;

class SetupBuildEnvironmentStep implements BuildStepInterface
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
        return 'Setting up Laravel build environment';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        if (!$projectConfiguration->getProjectType() instanceof LaravelProjectType) {
            throw new UnsupportedProjectException('You can only use this build step with Laravel projects');
        }

        $environmentName = $environmentConfiguration->getName();

        $envFile = $this->buildDirectory.'/.env';
        $environmentEnvFile = $envFile.'.'.$environmentName;

        if ($this->filesystem->exists($environmentEnvFile)) {
            $this->filesystem->copy($environmentEnvFile, $envFile, true);
            $this->filesystem->remove($environmentEnvFile);
        }

        if (!$this->filesystem->exists($envFile)) {
            throw new BuildFailedException('Unable to find a ".env" file in the build directory');
        }
    }
}
