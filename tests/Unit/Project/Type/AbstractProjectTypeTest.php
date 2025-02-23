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
use Ymir\Cli\Project\Type\AbstractProjectType;
use Ymir\Cli\Tests\Mock\FilesystemMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Type\AbstractProjectType
 */
class AbstractProjectTypeTest extends TestCase
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

    public function testGetEnvironmentConfigurationForProduction(): void
    {
        $projectType = $this->getMockForAbstractClass(AbstractProjectType::class, [$this->getFilesystemMock()]);

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
        ], $projectType->getEnvironmentConfiguration('production', ['foo' => 'bar']));
    }

    public function testGetEnvironmentConfigurationForStaging(): void
    {
        $projectType = $this->getMockForAbstractClass(AbstractProjectType::class, [$this->getFilesystemMock()]);

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
        ], $projectType->getEnvironmentConfiguration('staging', ['foo' => 'bar']));
    }

    public function testGetProjectFilesExcludesYmirYml()
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/ymir.yml');

        $projectType = $this->getMockForAbstractClass(AbstractProjectType::class, [$this->getFilesystemMock()]);

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetProjectFilesRespectsGitIgnore(): void
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/ignored.log');
        file_put_contents($this->tempDirectory.'/.gitignore', '*.log');

        $projectType = $this->getMockForAbstractClass(AbstractProjectType::class, [$this->getFilesystemMock()]);

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetSlug()
    {
        $projectType = $this->getMockForAbstractClass(AbstractProjectType::class, [$this->getFilesystemMock()]);

        $projectType->expects($this->once())
                    ->method('getName')
                    ->willReturn('Foo');

        $this->assertSame('foo', $projectType->getSlug());
    }
}
