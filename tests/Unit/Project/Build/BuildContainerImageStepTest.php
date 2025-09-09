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
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\Project\Build\BuildContainerImageStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Tests\TestCase;

class BuildContainerImageStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new BuildContainerImageStep('build', \Mockery::mock(DockerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame('Building container image', $step->getDescription());
    }

    public function testPerformBuildsContainerImage(): void
    {
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $environmentConfiguration->shouldReceive('getName')->andReturn('staging');
        $environmentConfiguration->shouldReceive('getArchitecture')->andReturn('amd64');
        $projectConfiguration->shouldReceive('getProjectName')->andReturn('project');
        $filesystem->shouldReceive('exists')
                   ->times(2)
                   ->with(\Mockery::any())
                   ->andReturnValues([false, true]);

        $dockerExecutable->shouldReceive('build')
                         ->once()
                         ->with('Dockerfile', 'project:staging', 'amd64', 'build');

        $step = new BuildContainerImageStep('build', $dockerExecutable, $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformThrowsExceptionIfDockerfileNotFound(): void
    {
        $this->expectException(BuildFailedException::class);
        $this->expectExceptionMessage('Unable to find a "Dockerfile" to build the container image');

        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $environmentConfiguration->shouldReceive('getName')->andReturn('staging');
        $filesystem->shouldReceive('exists')->andReturn(false);

        $step = new BuildContainerImageStep('build', $dockerExecutable, $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
