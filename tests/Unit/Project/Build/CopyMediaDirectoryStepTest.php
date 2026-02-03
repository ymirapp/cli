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

use PhpCsFixer\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Build\CopyMediaDirectoryStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Project\Type\SupportsMediaInterface;
use Ymir\Cli\Tests\TestCase;

class CopyMediaDirectoryStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new CopyMediaDirectoryStep(\Mockery::mock(Filesystem::class), 'uploads', 'project');

        $this->assertSame('Copying media directory', $step->getDescription());
    }

    public function testPerformCopiesEmptyMediaFilesUsingTouch(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class.', '.SupportsMediaInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

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

        $projectType->shouldReceive('getMediaFiles')->with('project')->andReturn(Finder::create()->append([$emptyFile, $notEmptyFile]));

        $filesystem->shouldReceive('touch')->once()->with('uploads/empty.txt');
        $filesystem->shouldReceive('copy')->once()->with('/path/to/not_empty.txt', 'uploads/not_empty.txt');

        $step = new CopyMediaDirectoryStep($filesystem, 'uploads', 'project');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformThrowsExceptionWithUnsupportedProjectType(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this build step with projects that support media operations');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $step = new CopyMediaDirectoryStep(\Mockery::mock(Filesystem::class), 'project', 'uploads');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
