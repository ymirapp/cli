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

namespace Unit\Project\Build\Laravel;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\Project\Build\Laravel\SetupBuildEnvironmentStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class SetupBuildEnvironmentStepTest extends TestCase
{
    /**
     * @var string
     */
    private $tempDirectory;

    protected function setUp(): void
    {
        $tempDirectory = sys_get_temp_dir().'/'.uniqid('ymir-test');

        (new Filesystem())->mkdir($tempDirectory);

        $this->tempDirectory = (string) realpath($tempDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDirectory);
    }

    public function testGetDescription(): void
    {
        $step = new SetupBuildEnvironmentStep('build', \Mockery::mock(Filesystem::class));

        $this->assertSame('Setting up Laravel build environment', $step->getDescription());
    }

    public function testPerformCopiesEnvironmentFileAndRemovesSourceFile(): void
    {
        file_put_contents($this->tempDirectory.'/.env', "APP_ENV=local\n");
        file_put_contents($this->tempDirectory.'/.env.staging', "APP_ENV=staging\n");

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $environmentConfiguration->shouldReceive('getName')->once()->andReturn('staging');
        $projectConfiguration->shouldReceive('getProjectType')->once()->andReturn($projectType);

        $step = new SetupBuildEnvironmentStep($this->tempDirectory.'/', new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileDoesNotExist($this->tempDirectory.'/.env.staging');
        $this->assertSame("APP_ENV=staging\n", file_get_contents($this->tempDirectory.'/.env'));
    }

    public function testPerformThrowsExceptionIfNoEnvFileFound(): void
    {
        $this->expectException(BuildFailedException::class);
        $this->expectExceptionMessage('Unable to find a ".env" file in the build directory');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $environmentConfiguration->shouldReceive('getName')->once()->andReturn('production');
        $projectConfiguration->shouldReceive('getProjectType')->once()->andReturn($projectType);

        $step = new SetupBuildEnvironmentStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformThrowsExceptionWithUnsupportedProjectType(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this build step with Laravel projects');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->once()->andReturn($projectType);

        $step = new SetupBuildEnvironmentStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformUsesDefaultEnvFileWhenEnvironmentFileIsMissing(): void
    {
        file_put_contents($this->tempDirectory.'/.env', "APP_ENV=local\n");

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $environmentConfiguration->shouldReceive('getName')->once()->andReturn('staging');
        $projectConfiguration->shouldReceive('getProjectType')->once()->andReturn($projectType);

        $step = new SetupBuildEnvironmentStep($this->tempDirectory, new Filesystem());

        $step->perform($environmentConfiguration, $projectConfiguration);

        $this->assertFileExists($this->tempDirectory.'/.env');
        $this->assertSame("APP_ENV=local\n", file_get_contents($this->tempDirectory.'/.env'));
    }
}
