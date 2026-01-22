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
use Ymir\Cli\Tests\TestCase;

class AbstractWordPressProjectTypeTest extends TestCase
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
    }

    public function testGenerateEnvironmentConfigurationForStaging(): void
    {
        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [\Mockery::mock(Filesystem::class)]);

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
        ], $projectType->generateEnvironmentConfiguration('staging', ['foo' => 'bar'])->toArray());
    }

    public function testGetAssetFilesFiltersOutMoFiles(): void
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/translation.mo');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [\Mockery::mock(Filesystem::class)]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesFiltersOutPhpFiles(): void
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/index.php');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [\Mockery::mock(Filesystem::class)]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesFiltersOutPoFiles(): void
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/language.po');

        $projectType = $this->getMockForAbstractClass(AbstractWordPressProjectType::class, [\Mockery::mock(Filesystem::class)]);

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }
}
