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

namespace Ymir\Cli\Tests\Unit;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Tests\TestCase;

class DockerfileTest extends TestCase
{
    private $filesystem;
    private $projectDir;
    private $stubDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = \Mockery::mock(Filesystem::class);
        $this->projectDir = sys_get_temp_dir().'/ymir-project';
        $this->stubDir = sys_get_temp_dir().'/ymir-stubs';
    }

    public function testConstructorThrowsExceptionIfStubNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Cannot find "Dockerfile" stub file');

        $this->filesystem->shouldReceive('exists')->once()->with($this->stubDir.'/Dockerfile')->andReturn(false);

        new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
    }

    public function testCreateCopiesStubToFile(): void
    {
        $this->filesystem->shouldReceive('exists')->once()->with($this->stubDir.'/Dockerfile')->andReturn(true);
        $this->filesystem->shouldReceive('copy')->once()->with($this->stubDir.'/Dockerfile', $this->projectDir.'/Dockerfile', true);

        $dockerfile = new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
        $dockerfile->create();
    }

    public function testCreateWithEnvironmentCopiesStubToFile(): void
    {
        $this->filesystem->shouldReceive('exists')->once()->with($this->stubDir.'/Dockerfile')->andReturn(true);
        $this->filesystem->shouldReceive('copy')->once()->with($this->stubDir.'/Dockerfile', $this->projectDir.'/staging.Dockerfile', true);

        $dockerfile = new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
        $dockerfile->create('staging');
    }

    public function testExistsReturnsTrueIfDefaultExists(): void
    {
        $this->filesystem->shouldReceive('exists')->with($this->stubDir.'/Dockerfile')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with($this->projectDir.'/Dockerfile')->andReturn(true);

        $dockerfile = new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
        $this->assertTrue($dockerfile->exists('staging'));
    }

    public function testExistsReturnsTrueIfEnvironmentSpecificExists(): void
    {
        $this->filesystem->shouldReceive('exists')->with($this->stubDir.'/Dockerfile')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with($this->projectDir.'/Dockerfile')->andReturn(false);
        $this->filesystem->shouldReceive('exists')->with($this->projectDir.'/staging.Dockerfile')->andReturn(true);

        $dockerfile = new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
        $this->assertTrue($dockerfile->exists('staging'));
    }

    public function testValidatePassesIfArchitectureAndImageMatch(): void
    {
        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM ymirapp/php-runtime:8.1\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        $dockerfile->validate('staging', 'x86_64');

        file_put_contents($tempProjectDir.'/Dockerfile', "FROM ymirapp/arm-php-runtime:8.1\n");
        $dockerfile->validate('staging', 'arm64');

        $filesystem->remove([$tempProjectDir, $stubDir]);
        $this->assertTrue(true); // If we reach here, it passed
    }

    public function testValidatePassesIfPlatformMatchesArchitecture(): void
    {
        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM --platform=linux/amd64 alpine:latest\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        $dockerfile->validate('staging', 'x86_64');

        file_put_contents($tempProjectDir.'/Dockerfile', "FROM --platform=linux/arm64 alpine:latest\n");
        $dockerfile->validate('staging', 'arm64');

        $filesystem->remove([$tempProjectDir, $stubDir]);
        $this->assertTrue(true);
    }

    public function testValidateThrowsExceptionIfArmArchitectureButX86Image(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('You must use the "ymirapp/arm-php-runtime" image with the "arm64" architecture');

        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM ymirapp/php-runtime:8.1\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        try {
            $dockerfile->validate('staging', 'arm64');
        } finally {
            $filesystem->remove([$tempProjectDir, $stubDir]);
        }
    }

    public function testValidateThrowsExceptionIfArmArchitectureButX86Platform(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('The "--platform" flag in the "Dockerfile" FROM instruction must be "linux/arm64" when using the "arm64" architecture, "linux/amd64" given');

        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM --platform=linux/amd64 alpine:latest\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        try {
            $dockerfile->validate('staging', 'arm64');
        } finally {
            $filesystem->remove([$tempProjectDir, $stubDir]);
        }
    }

    public function testValidateThrowsExceptionIfDockerfileNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Unable to find a "Dockerfile" in the project directory');

        $this->filesystem->shouldReceive('exists')->with($this->stubDir.'/Dockerfile')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with($this->projectDir.'/Dockerfile')->andReturn(false);
        $this->filesystem->shouldReceive('exists')->with($this->projectDir.'/staging.Dockerfile')->andReturn(false);

        $dockerfile = new Dockerfile($this->filesystem, $this->projectDir, $this->stubDir);
        $dockerfile->validate('staging');
    }

    public function testValidateThrowsExceptionIfX86ArchitectureButArmImage(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('You must use the "ymirapp/php-runtime" image with the "x86_64" architecture');

        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM ymirapp/arm-php-runtime:8.1\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        try {
            $dockerfile->validate('staging', 'x86_64');
        } finally {
            $filesystem->remove([$tempProjectDir, $stubDir]);
        }
    }

    public function testValidateThrowsExceptionIfX86ArchitectureButArmPlatform(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('The "--platform" flag in the "Dockerfile" FROM instruction must be "linux/amd64" when using the "x86_64" architecture, "linux/arm64" given');

        $tempProjectDir = sys_get_temp_dir().'/ymir-project-'.uniqid();
        mkdir($tempProjectDir);
        file_put_contents($tempProjectDir.'/Dockerfile', "FROM --platform=linux/arm64 alpine:latest\n");

        $filesystem = new Filesystem();
        $stubDir = sys_get_temp_dir().'/ymir-stub-'.uniqid();
        mkdir($stubDir);
        touch($stubDir.'/Dockerfile');

        $dockerfile = new Dockerfile($filesystem, $tempProjectDir, $stubDir);

        try {
            $dockerfile->validate('staging', 'x86_64');
        } finally {
            $filesystem->remove([$tempProjectDir, $stubDir]);
        }
    }
}
