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
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;

class CopyMustUsePluginStep implements BuildStepInterface
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
     * The directory where the stub files are.
     *
     * @var string
     */
    private $stubDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem, string $stubDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->stubDirectory = rtrim($stubDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Copying Ymir must-use plugin';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $mupluginStub = 'activate-ymir-plugin.php';
        $mupluginStubPath = $this->stubDirectory.'/'.$mupluginStub;

        if (!$this->filesystem->exists($mupluginStubPath)) {
            throw new RuntimeException(sprintf('Cannot find "%s" stub file', $mupluginStub));
        }

        $projectType = $projectConfiguration->getProjectType();

        if (!$projectType instanceof AbstractWordPressProjectType) {
            throw new RuntimeException('You can only use this build step with WordPress projects');
        }

        $mupluginsDirectory = $projectType->getMustUsePluginsDirectoryPath($this->buildDirectory);

        if (!$this->filesystem->exists($mupluginsDirectory)) {
            $this->filesystem->mkdir($mupluginsDirectory);
        }

        $this->filesystem->copy($mupluginStubPath, $mupluginsDirectory.'/'.$mupluginStub);
    }
}
