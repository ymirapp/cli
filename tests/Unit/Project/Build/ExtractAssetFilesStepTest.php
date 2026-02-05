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
use Ymir\Cli\Project\Build\ExtractAssetFilesStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class ExtractAssetFilesStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new ExtractAssetFilesStep('assets', 'build', \Mockery::mock(Filesystem::class));

        $this->assertSame('Extracting asset files', $step->getDescription());
    }

    public function testPerformExtractsAssetFiles(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getAssetFiles')->andReturn(Finder::create()->append([]));

        $filesystem->shouldReceive('exists')->once()
                   ->with('assets')
                   ->andReturn(true);
        $filesystem->shouldReceive('remove')->once()
                   ->with('assets');
        $filesystem->shouldReceive('mkdir')->once()
                   ->with('assets', 0755);

        $step = new ExtractAssetFilesStep('assets', 'build', $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformExtractsEmptyAssetFileInSubdirectory(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $filesystem = new Filesystem();
        $tempDir = sys_get_temp_dir().'/ymir-test-'.uniqid();
        $assetsDir = $tempDir.'/assets';

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $emptyFile = \Mockery::mock(SplFileInfo::class);
        $emptyFile->shouldReceive('isFile')->andReturn(true);
        $emptyFile->shouldReceive('getSize')->andReturn(0);
        $emptyFile->shouldReceive('getRelativePathname')->andReturn('subdir/empty.txt');
        $emptyFile->shouldReceive('getPathname')->andReturn('subdir/empty.txt');

        $projectType->shouldReceive('getAssetFiles')->andReturn(Finder::create()->append([$emptyFile]));

        $step = new ExtractAssetFilesStep($assetsDir, $tempDir.'/build', $filesystem);

        try {
            $step->perform($environmentConfiguration, $projectConfiguration);
            $this->assertFileExists($assetsDir.'/subdir/empty.txt');
        } finally {
            $filesystem->remove($tempDir);
        }
    }

    public function testPerformExtractsEmptyAssetFilesUsingTouch(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $emptyFile = \Mockery::mock(SplFileInfo::class);
        $emptyFile->shouldReceive('isFile')->andReturn(true);
        $emptyFile->shouldReceive('getSize')->andReturn(0);
        $emptyFile->shouldReceive('getRelativePathname')->andReturn('empty.txt');
        $emptyFile->shouldReceive('getPathname')->andReturn('empty.txt');

        $notEmptyFile = \Mockery::mock(SplFileInfo::class);
        $notEmptyFile->shouldReceive('isFile')->andReturn(true);
        $notEmptyFile->shouldReceive('getSize')->andReturn(10);
        $notEmptyFile->shouldReceive('getRelativePathname')->andReturn('not_empty.txt');
        $notEmptyFile->shouldReceive('getPathname')->andReturn('not_empty.txt');
        $notEmptyFile->shouldReceive('getRealPath')->andReturn('/path/to/not_empty.txt');

        $projectType->shouldReceive('getAssetFiles')->andReturn(Finder::create()->append([$emptyFile, $notEmptyFile]));

        $filesystem->shouldReceive('exists')->andReturn(false);
        $filesystem->shouldReceive('mkdir')->with('assets', 0755);
        $filesystem->shouldReceive('mkdir')->with('assets');

        $filesystem->shouldReceive('touch')->once()->with('assets/empty.txt');
        $filesystem->shouldReceive('copy')->once()->with('/path/to/not_empty.txt', 'assets/not_empty.txt');

        $step = new ExtractAssetFilesStep('assets', 'build', $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
