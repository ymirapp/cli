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
use Symfony\Component\Finder\SplFileInfo;
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

    public function testPerformCopiesEmptyFileInSubdirectory(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $filesystem = new Filesystem();
        $tempDir = sys_get_temp_dir().'/ymir-test-'.uniqid();
        $buildDir = $tempDir.'/build';

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);
        $environmentConfiguration->shouldReceive('isImageDeploymentType')->andReturn(false);

        $emptyFile = \Mockery::mock(SplFileInfo::class);
        $emptyFile->shouldReceive('isFile')->andReturn(true);
        $emptyFile->shouldReceive('isDir')->andReturn(false);
        $emptyFile->shouldReceive('getSize')->andReturn(0);
        $emptyFile->shouldReceive('getRelativePathname')->andReturn('subdir/empty.txt');
        $emptyFile->shouldReceive('getPathname')->andReturn('subdir/empty.txt');

        $projectType->shouldReceive('getProjectFiles')->andReturn(Finder::create()->append([$emptyFile]));

        $step = new CopyProjectFilesStep($buildDir, $filesystem, $tempDir.'/project');

        try {
            $step->perform($environmentConfiguration, $projectConfiguration);
            $this->assertFileExists($buildDir.'/subdir/empty.txt');
        } finally {
            $filesystem->remove($tempDir);
        }
    }

    public function testPerformCopiesEmptyFilesUsingTouch(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);
        $environmentConfiguration->shouldReceive('isImageDeploymentType')->andReturn(false);

        $filesystem->shouldReceive('exists')->andReturn(false);
        $filesystem->shouldReceive('mkdir')->with('build', 0755);
        $filesystem->shouldReceive('mkdir')->with('build');

        $emptyFile = \Mockery::mock(SplFileInfo::class);
        $emptyFile->shouldReceive('isFile')->andReturn(true);
        $emptyFile->shouldReceive('isDir')->andReturn(false);
        $emptyFile->shouldReceive('getSize')->andReturn(0);
        $emptyFile->shouldReceive('getRelativePathname')->andReturn('empty.txt');
        $emptyFile->shouldReceive('getPathname')->andReturn('empty.txt');

        $notEmptyFile = \Mockery::mock(SplFileInfo::class);
        $notEmptyFile->shouldReceive('isFile')->andReturn(true);
        $notEmptyFile->shouldReceive('isDir')->andReturn(false);
        $notEmptyFile->shouldReceive('getSize')->andReturn(10);
        $notEmptyFile->shouldReceive('getRelativePathname')->andReturn('not_empty.txt');
        $notEmptyFile->shouldReceive('getPathname')->andReturn('not_empty.txt');
        $notEmptyFile->shouldReceive('getRealPath')->andReturn('/path/to/not_empty.txt');

        $projectType->shouldReceive('getProjectFiles')->andReturn(Finder::create()->append([$emptyFile, $notEmptyFile]));

        $filesystem->shouldReceive('touch')->once()->with('build/empty.txt');
        $filesystem->shouldReceive('copy')->once()->with('/path/to/not_empty.txt', 'build/not_empty.txt');

        $step = new CopyProjectFilesStep('build', $filesystem, 'project');

        $step->perform($environmentConfiguration, $projectConfiguration);
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
