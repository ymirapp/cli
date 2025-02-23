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

namespace Ymir\Cli\Tests\Unit\Project\Type;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\Mock\FilesystemMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Type\AbstractWordPressProjectType
 */
class AbstractWordPressProjectTypeTest extends TestCase
{
    use FilesystemMockTrait;

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
    }

    public function testGetAssetFilesFiltersOutMoFiles()
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/translation.mo');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [$this->getFilesystemMock()]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesFiltersOutPhpFiles()
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/index.php');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [$this->getFilesystemMock()]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesFiltersOutPoFiles()
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/language.po');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [$this->getFilesystemMock()]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetEnvironmentConfigurationForStaging(): void
    {
        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [$this->getFilesystemMock()]);

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
            'cdn' => [
                'caching' => 'assets',
            ],
        ], $projectType->getEnvironmentConfiguration('staging', ['foo' => 'bar']));
    }
}
