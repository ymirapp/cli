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
use Ymir\Cli\Tests\TestCase;

class AbstractProjectTypeTest extends TestCase
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

    public function testGenerateEnvironmentConfigurationForProduction(): void
    {
        $projectType = \Mockery::mock(AbstractProjectType::class, [\Mockery::mock(Filesystem::class)])->makePartial();

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
        ], $projectType->generateEnvironmentConfiguration('production', ['foo' => 'bar'])->toArray());
    }

    public function testGenerateEnvironmentConfigurationForStaging(): void
    {
        $projectType = \Mockery::mock(AbstractProjectType::class, [\Mockery::mock(Filesystem::class)])->makePartial();

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
        ], $projectType->generateEnvironmentConfiguration('staging', ['foo' => 'bar'])->toArray());
    }

    public function testGetProjectFilesExcludesYmirYml(): void
    {
        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/ymir.yml');

        $projectType = \Mockery::mock(AbstractProjectType::class, [\Mockery::mock(Filesystem::class)])->makePartial();

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

        $projectType = \Mockery::mock(AbstractProjectType::class, [\Mockery::mock(Filesystem::class)])->makePartial();

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetSlug(): void
    {
        $projectType = \Mockery::mock(AbstractProjectType::class, [\Mockery::mock(Filesystem::class)])->makePartial();

        $projectType->shouldReceive('getName')->once()
                    ->andReturn('Foo');

        $this->assertSame('foo', $projectType->getSlug());
    }
}
