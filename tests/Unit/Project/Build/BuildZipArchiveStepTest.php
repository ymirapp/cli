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
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Project\Build\BuildZipArchiveStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class BuildZipArchiveStepTest extends TestCase
{
    /**
     * @var string
     */
    private $buildArchivePath;

    /**
     * @var string
     */
    private $tempDirectory;

    protected function setUp(): void
    {
        $tempDirectory = sys_get_temp_dir().'/'.uniqid('ymir-test');

        (new Filesystem())->mkdir($tempDirectory);

        $this->tempDirectory = realpath($tempDirectory);
        $this->buildArchivePath = $this->tempDirectory.'/build.zip';
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDirectory);

        parent::tearDown();
    }

    public function testGetDescription(): void
    {
        $step = new BuildZipArchiveStep($this->buildArchivePath, $this->tempDirectory);

        $this->assertSame('Building zip archive', $step->getDescription());
    }

    public function testPerformCreatesZipArchiveWithCorrectFiles(): void
    {
        $this->createFile('file1.txt', 'content1');
        $this->createFile('dir/file2.txt', 'content2');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getArchiveFiles')->with($this->tempDirectory)->andReturn(Finder::create()->in($this->tempDirectory)->files()->notName('build.zip'));
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);

        $step = new BuildZipArchiveStep($this->buildArchivePath, $this->tempDirectory);
        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->buildArchivePath);

        $zip = new \ZipArchive();
        $zip->open($this->buildArchivePath);

        $this->assertSame(2, $zip->numFiles);
        $this->assertSame('content1', $zip->getFromName('file1.txt'));
        $this->assertSame('content2', $zip->getFromName('dir/file2.txt'));

        $zip->close();
    }

    public function testPerformIncludesFilesFromIncludePaths(): void
    {
        $this->createFile('archive/file1.txt', 'content1');
        $this->createFile('include/file2.txt', 'content2');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getArchiveFiles')->with($this->tempDirectory)->andReturn(Finder::create()->in($this->tempDirectory)->path('archive/')->files());
        $projectType->shouldReceive('getIncludedFiles')->with($this->tempDirectory, ['include/'])->andReturn(Finder::create()->in($this->tempDirectory)->path('include/')->files());
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn(['include/']);

        $step = new BuildZipArchiveStep($this->buildArchivePath, $this->tempDirectory);
        $step->perform($environmentConfiguration, $projectConfiguration);

        $zip = new \ZipArchive();
        $zip->open($this->buildArchivePath);

        $this->assertSame(2, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('archive/file1.txt'));
        $this->assertNotFalse($zip->locateName('include/file2.txt'));

        $zip->close();
    }

    public function testPerformThrowsExceptionWhenSizeExceedsLimit(): void
    {
        $this->expectException(BuildFailedException::class);
        $this->expectExceptionMessage('The uncompressed build is 147005412 bytes');

        $this->createFile('foo.txt', '');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $file = \Mockery::mock(SplFileInfo::class);
        $file->shouldReceive('isFile')->andReturn(true);
        $file->shouldReceive('getRealPath')->andReturn($this->tempDirectory.'/foo.txt');
        $file->shouldReceive('getRelativePathname')->andReturn('foo.txt');
        $file->shouldReceive('getPathname')->andReturn('foo.txt');
        $file->shouldReceive('getSize')->andReturn(147005412);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getArchiveFiles')->with($this->tempDirectory)->andReturn(Finder::create()->append([$file]));
        $environmentConfiguration->shouldReceive('getBuildIncludePaths')->andReturn([]);

        $step = new BuildZipArchiveStep($this->buildArchivePath, $this->tempDirectory);
        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    /**
     * Create a file in the temp directory.
     */
    private function createFile(string $path, string $content): void
    {
        $fullPath = $this->tempDirectory.'/'.$path;

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        file_put_contents($fullPath, $content);
    }
}
