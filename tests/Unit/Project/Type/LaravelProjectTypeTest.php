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
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\Project\Build;
use Ymir\Cli\Project\Initialization;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Tests\TestCase;

class LaravelProjectTypeTest extends TestCase
{
    /**
     * @var string
     */
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
        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'php' => '8.3',
            'foo' => 'bar',
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev',
                'npm ci && npm run build',
            ],
            'cdn' => [
                'cookies_whitelist' => [
                    'laravel_session',
                    'laravel-session',
                    'remember_*',
                    'XSRF-TOKEN',
                ],
                'excluded_paths' => [
                    '/login',
                ],
            ],
            'queues' => true,
        ], $projectType->generateEnvironmentConfiguration('production', ['foo' => 'bar'])->toArray());
    }

    public function testGenerateEnvironmentConfigurationForStaging(): void
    {
        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'php' => '8.3',
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install',
                'npm ci && npm run build',
            ],
            'cdn' => [
                'cookies_whitelist' => [
                    'laravel_session',
                    'laravel-session',
                    'remember_*',
                    'XSRF-TOKEN',
                ],
                'excluded_paths' => [
                    '/login',
                ],
            ],
            'queues' => true,
        ], $projectType->generateEnvironmentConfiguration('staging', ['foo' => 'bar'])->toArray());
    }

    public function testGetAssetFilesExcludesFilesOutsidePublicDirectory(): void
    {
        mkdir($this->tempDirectory.'/public', 0777, true);

        $keepFilePath = $this->tempDirectory.'/public/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/excluded.txt');

        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory)->files(), false);

        $this->assertCount(1, $files);
        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetBuildSteps(): void
    {
        $this->assertSame([
            Build\CopyProjectFilesStep::class,
            Build\Laravel\SetupBuildEnvironmentStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\CleanupBuildStep::class,
            Build\ExtractAssetFilesStep::class,
        ], (new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getBuildSteps());
    }

    public function testGetExcludedFilesExcludesDotEnvButNotDotEnvEncrypted(): void
    {
        touch($this->tempDirectory.'/.env');
        touch($this->tempDirectory.'/.env.encrypted');

        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), new Filesystem());

        $files = iterator_to_array($projectType->getExcludedFiles($this->tempDirectory)->files(), false);
        $paths = array_map(function ($file): string {
            return $file->getRelativePathname();
        }, $files);

        $this->assertContains('.env', $paths);
        $this->assertNotContains('.env.encrypted', $paths);
    }

    public function testGetInitializationSteps(): void
    {
        $this->assertSame([
            Initialization\DatabaseInitializationStep::class,
            Initialization\CacheInitializationStep::class,
            Initialization\DockerInitializationStep::class,
            Initialization\IntegrationInitializationStep::class,
            Initialization\VaporConfigurationInitializationStep::class,
        ], (new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getInitializationSteps());
    }

    public function testGetInstallationMessage(): void
    {
        $this->assertSame('Creating new Laravel project using Composer', (new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getInstallationMessage());
    }

    public function testGetName(): void
    {
        $this->assertSame('Laravel', (new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getName());
    }

    public function testGetProjectFilesIncludesDotEnvFilesButNotDotEnvExample(): void
    {
        touch($this->tempDirectory.'/keep.txt');
        touch($this->tempDirectory.'/.env');
        touch($this->tempDirectory.'/.env.staging');
        touch($this->tempDirectory.'/.env.example');
        file_put_contents($this->tempDirectory.'/.gitignore', '.env*');

        $projectType = new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory)->files(), false);
        $paths = array_map(function ($file): string {
            return $file->getRelativePathname();
        }, $files);

        $this->assertContains('keep.txt', $paths);
        $this->assertContains('.env', $paths);
        $this->assertContains('.env.staging', $paths);
        $this->assertNotContains('.env.example', $paths);
    }

    public function testGetSlug(): void
    {
        $this->assertSame('laravel', (new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getSlug());
    }

    public function testInstallIntegration(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('require')->once()
                           ->with($this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo('/path/to/project'));

        (new LaravelProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->installIntegration('/path/to/project');
    }

    public function testInstallProject(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('createProject')->once()
                           ->with($this->identicalTo('laravel/laravel'), $this->identicalTo('/path/to/project'));

        (new LaravelProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->installProject('/path/to/project');
    }

    public function testIsEligibleForInstallationReturnsFalseWhenDirectoryIsNotEmpty(): void
    {
        touch($this->tempDirectory.'/dummy.txt');

        $this->assertFalse((new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsTrueWhenDirectoryIsEmpty(): void
    {
        $this->assertTrue((new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenPackageIsNotInstalled(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(false);

        $this->assertFalse((new LaravelProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsTrueWhenPackageIsInstalled(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(true);

        $this->assertTrue((new LaravelProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenArtisanIsMissing(): void
    {
        touch($this->tempDirectory.'/composer.json');
        mkdir($this->tempDirectory.'/public', 0777, true);
        touch($this->tempDirectory.'/public/index.php');

        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldNotReceive('isPackageInstalled');

        $this->assertFalse((new LaravelProjectType($composerExecutable, new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenComposerJsonIsMissing(): void
    {
        touch($this->tempDirectory.'/artisan');
        mkdir($this->tempDirectory.'/public', 0777, true);
        touch($this->tempDirectory.'/public/index.php');

        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldNotReceive('isPackageInstalled');

        $this->assertFalse((new LaravelProjectType($composerExecutable, new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenLaravelFrameworkPackageIsMissing(): void
    {
        touch($this->tempDirectory.'/artisan');
        touch($this->tempDirectory.'/composer.json');
        mkdir($this->tempDirectory.'/public', 0777, true);
        touch($this->tempDirectory.'/public/index.php');

        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('laravel/framework'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(false);

        $this->assertFalse((new LaravelProjectType($composerExecutable, new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenPublicIndexIsMissing(): void
    {
        touch($this->tempDirectory.'/artisan');
        touch($this->tempDirectory.'/composer.json');

        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldNotReceive('isPackageInstalled');

        $this->assertFalse((new LaravelProjectType($composerExecutable, new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueForLaravelStructureAndPackage(): void
    {
        touch($this->tempDirectory.'/artisan');
        touch($this->tempDirectory.'/composer.json');
        mkdir($this->tempDirectory.'/public', 0777, true);
        touch($this->tempDirectory.'/public/index.php');

        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('laravel/framework'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(true);

        $this->assertTrue((new LaravelProjectType($composerExecutable, new Filesystem()))->matchesProject($this->tempDirectory));
    }
}
