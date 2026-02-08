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
use Ymir\Cli\Project\Build\CleanupBuildStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class CleanupBuildStepTest extends TestCase
{
    private $tempDirectory;

    protected function setUp(): void
    {
        $tempDirectory = sys_get_temp_dir().'/'.uniqid('ymir-test');
        (new Filesystem())->mkdir($tempDirectory);
        $this->tempDirectory = realpath($tempDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDirectory);
        parent::tearDown();
    }

    public function testGetDescription(): void
    {
        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $this->assertSame('Cleaning up build', $step->getDescription());
    }

    public function testPerformCombinesExclusionsFromMultipleSourcesUsingOrLogic(): void
    {
        $this->createFile('project-exclude/file.ts');
        $this->createFile('env-exclude/file.txt');
        $this->createFile('keep.txt');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $projectType = \Mockery::mock(\Ymir\Cli\Project\Type\AbstractProjectType::class, [new Filesystem()])->makePartial();
        $projectType->shouldReceive('getName')->andReturn('test');

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $environmentConfiguration->shouldReceive('getBuildExcludePaths')->andReturn(['env-exclude/']);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);

        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->tempDirectory.'/keep.txt');
        $this->assertFileDoesNotExist($this->tempDirectory.'/project-exclude/file.ts');
        $this->assertFileDoesNotExist($this->tempDirectory.'/env-exclude/file.txt');
    }

    public function testPerformDoesNotDeleteEverythingWhenNoCustomExclusionsAreConfigured(): void
    {
        $this->createFile('keep.txt');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $projectType = \Mockery::mock(\Ymir\Cli\Project\Type\AbstractProjectType::class, [new Filesystem()])->makePartial();
        $projectType->shouldReceive('getName')->andReturn('test');

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $environmentConfiguration->shouldReceive('getBuildExcludePaths')->andReturn([]);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);

        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->tempDirectory.'/keep.txt');
    }

    public function testPerformRemovesFilesFromBothProjectTypeAndEnvironmentConfiguration(): void
    {
        $this->createFile('project-exclude/remove.txt');
        $this->createFile('env-exclude/remove.txt');
        $this->createFile('env-exclude/keep.txt');
        $this->createFile('keep.txt');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getExcludedFiles')
            ->with($this->tempDirectory)
            ->andReturn(Finder::create()->in($this->tempDirectory)->path('project-exclude/'));

        $environmentConfiguration->shouldReceive('getBuildExcludePaths')->andReturn(['env-exclude/']);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn(['env-exclude/keep.txt']);

        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileDoesNotExist($this->tempDirectory.'/project-exclude/remove.txt');
        $this->assertFileDoesNotExist($this->tempDirectory.'/env-exclude/remove.txt');
        $this->assertFileExists($this->tempDirectory.'/env-exclude/keep.txt');
        $this->assertFileExists($this->tempDirectory.'/keep.txt');
    }

    public function testPerformRemovesFilesSpecifiedInEnvironmentConfiguration(): void
    {
        $this->createFile('keep.txt');
        $this->createFile('exclude-path/remove.txt');
        $this->createFile('exclude-path/keep-included.txt');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getExcludedFiles')
            ->with($this->tempDirectory)
            ->andReturn(Finder::create());

        $environmentConfiguration->shouldReceive('getBuildExcludePaths')->andReturn(['exclude-path/']);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn(['exclude-path/keep-included.txt']);

        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->tempDirectory.'/keep.txt');
        $this->assertFileDoesNotExist($this->tempDirectory.'/exclude-path/remove.txt');
        $this->assertFileExists($this->tempDirectory.'/exclude-path/keep-included.txt');
    }

    public function testPerformRespectsIncludePathsOverDefaultExclusions(): void
    {
        $this->createFile('node_modules/keep-included.txt');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $projectType->shouldReceive('getExcludedFiles')
            ->with($this->tempDirectory)
            ->andReturn(Finder::create()->in($this->tempDirectory)->path('node_modules/'));

        $environmentConfiguration->shouldReceive('getBuildExcludePaths')->andReturn([]);
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn(['node_modules/keep-included.txt']);

        $step = new CleanupBuildStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->tempDirectory.'/node_modules/keep-included.txt');
    }

    private function createFile(string $path): void
    {
        $fullPath = $this->tempDirectory.'/'.$path;

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        touch($fullPath);
    }
}
