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

namespace Ymir\Cli\Tests\Unit\Project\Build;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Project\Build\CopyProjectFilesStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class CopyProjectFilesStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new CopyProjectFilesStep('build', \Mockery::mock(Filesystem::class), 'project');

        $this->assertSame('Copying Project files', $step->getDescription());
    }

    public function testPerformCopiesFiles(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);
        $environmentConfiguration->shouldReceive('getDeploymentType')->andReturn('zip');
        $environmentConfiguration->shouldReceive('isImageDeploymentType')->andReturn(false);

        $filesystem->shouldReceive('exists')->once()
                   ->with('build')
                   ->andReturn(true);
        $filesystem->shouldReceive('remove')->once()
                   ->with('build');
        $filesystem->shouldReceive('mkdir')->once()
                   ->with('build', 0755);

        $projectType->shouldReceive('getProjectFiles')->once()
                    ->with('project')
                    ->andReturn(Finder::create()->append([]));

        $step = new CopyProjectFilesStep('build', $filesystem, 'project');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
