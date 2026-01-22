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
use Ymir\Cli\Project\Type\BedrockProjectType;
use Ymir\Cli\Tests\TestCase;

class BedrockProjectTypeTest extends TestCase
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

    public static function provideRequiredFileTypes(): array
    {
        return [
            ['index.php'],
            ['translation.mo'],
        ];
    }

    public static function provideRequiredThemeFiles(): array
    {
        return [
            ['block.json'],
            ['screenshot.gif'],
            ['screenshot.png'],
            ['screenshot.jpg'],
            ['screenshot.jpeg'],
            ['style.css'],
            ['theme.json'],
        ];
    }

    public static function provideRequiredWordPressCoreFiles(): array
    {
        return [
            ['web/wp/wp-admin/included.txt'],
            ['web/wp/wp-includes/included.txt'],
        ];
    }

    public function testGenerateEnvironmentConfigurationForProduction(): void
    {
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev',
            ],
        ], $projectType->generateEnvironmentConfiguration('production', ['foo' => 'bar'])->toArray());
    }

    public function testGenerateEnvironmentConfigurationForStaging(): void
    {
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install',
            ],
        ], $projectType->generateEnvironmentConfiguration('staging', ['foo' => 'bar'])->toArray());
    }

    public function testGetAssetFilesExcludesFilesBelowWebDirectory(): void
    {
        mkdir($this->tempDirectory.'/web', 0777, true);

        $keepFilePath = $this->tempDirectory.'/web/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/excluded.txt');

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesExcludesFilesInWpContent(): void
    {
        mkdir($this->tempDirectory.'/web/wp/wp-content', 0777, true);

        $keepFilePath = $this->tempDirectory.'/web/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/web/wp/wp-content/excluded.txt');

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetBuildFilesExcludesWpContent(): void
    {
        $wpContentDirectory = $this->tempDirectory.'/web/wp/wp-content';

        mkdir($wpContentDirectory, 0777, true);

        touch($wpContentDirectory.'/excluded.txt');

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertEmpty($files);
    }

    /**
     * @dataProvider provideRequiredFileTypes
     */
    public function testGetBuildFilesIncludesRequiredFileTypes(string $filename): void
    {
        $requiredFile = $this->tempDirectory.'/'.$filename;

        touch($this->tempDirectory.'/excluded.txt');
        touch($requiredFile);

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesRequiredPluginFiles(): void
    {
        mkdir($this->tempDirectory.'/web/app/plugins/foo', 0777, true);

        $blockJsonPath = $this->tempDirectory.'/web/app/plugins/foo/block.json';

        touch($this->tempDirectory.'/excluded.txt');
        touch($blockJsonPath);

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($blockJsonPath, $files[0]->getPathname());
    }

    /**
     * @dataProvider provideRequiredThemeFiles
     */
    public function testGetBuildFilesIncludesRequiredThemeFiles(string $filename): void
    {
        $themeDirectory = $this->tempDirectory.'/web/app/themes/foo';

        mkdir($themeDirectory, 0777, true);

        $requiredThemeFile = $themeDirectory.'/'.$filename;

        touch($themeDirectory.'/excluded.txt');
        touch($requiredThemeFile);

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredThemeFile, $files[0]->getPathname());
    }

    /**
     * @dataProvider provideRequiredWordPressCoreFiles
     */
    public function testGetBuildFilesIncludesRequiredWordPressCoreFiles(string $filename): void
    {
        $requiredFile = $this->tempDirectory.'/'.$filename;

        if (!is_dir(dirname($requiredFile))) {
            mkdir(dirname($requiredFile), 0777, true);
        }

        touch($this->tempDirectory.'/excluded.txt');
        touch($requiredFile);

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesWpCliYml(): void
    {
        $wpCliYmlPath = $this->tempDirectory.'/wp-cli.yml';

        touch($this->tempDirectory.'/excluded.txt');
        touch($wpCliYmlPath);

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($wpCliYmlPath, $files[0]->getPathname());
    }

    public function testGetBuildSteps(): void
    {
        $this->assertSame([
            Build\CopyProjectFilesStep::class,
            Build\DownloadWpCliStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\CopyMustUsePluginStep::class,
            Build\ExtractAssetFilesStep::class,
        ], (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getBuildSteps());
    }

    public function testGetInitializationSteps(): void
    {
        $this->assertSame([
            Initialization\DatabaseInitializationStep::class,
            Initialization\CacheInitializationStep::class,
            Initialization\DockerInitializationStep::class,
            Initialization\IntegrationInitializationStep::class,
            Initialization\WordPressConfigurationInitializationStep::class,
        ], (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getInitializationSteps());
    }

    public function testGetInstallationMessage(): void
    {
        $this->assertSame('Creating new Bedrock project using Composer', (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getInstallationMessage());
    }

    public function testGetMediaDirectoryName(): void
    {
        $this->assertSame('uploads', (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getMediaDirectoryName());
    }

    public function testGetMediaDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'web/app/uploads', $projectType->getMediaDirectoryPath($buildDir));
    }

    public function testGetMediaDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'/web/app/uploads', $projectType->getMediaDirectoryPath($buildDir));
    }

    public function testGetMediaDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame('web/app/uploads', $projectType->getMediaDirectoryPath());
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'/web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame('web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath());
    }

    public function testGetName(): void
    {
        $this->assertSame('Bedrock', (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getName());
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'web/app/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame($buildDir.'/web/app/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $this->assertSame('web/app/plugins', $projectType->getPluginsDirectoryPath());
    }

    public function testGetProjectFilesExcludesUploadsDirectory(): void
    {
        mkdir($this->tempDirectory.'/web/app/uploads', 0777, true);

        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/web/app/uploads/excluded.txt');

        $projectType = new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetSlug(): void
    {
        $this->assertSame('bedrock', (new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->getSlug());
    }

    public function testInstallIntegration(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('require')->once()
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo('/path/to/project'));

        (new BedrockProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->installIntegration('/path/to/project');
    }

    public function testInstallProject(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('createProject')->once()
                           ->with($this->identicalTo('roots/bedrock'), $this->identicalTo('/path/to/project'));

        (new BedrockProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->installProject('/path/to/project');
    }

    public function testIsEligibleForInstallationReturnsFalseWhenDirectoryIsNotEmpty(): void
    {
        touch($this->tempDirectory.'/dummy.txt');

        $this->assertFalse((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsTrueWhenDirectoryIsEmpty(): void
    {
        $this->assertTrue((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class)))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenWordPressPluginIsNotInstalled(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(false);

        $this->assertFalse((new BedrockProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsTrueWhenWordPressPluginIsInstalled(): void
    {
        $composerExecutable = \Mockery::mock(ComposerExecutable::class);

        $composerExecutable->shouldReceive('isPackageInstalled')->once()
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory))
                           ->andReturn(true);

        $this->assertTrue((new BedrockProjectType($composerExecutable, \Mockery::mock(Filesystem::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenAppDirectoryIsMissing(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web', 0777, true);

        touch($this->tempDirectory.'/config/application.php');
        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertFalse((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenApplicationConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertFalse((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/config/application.php');

        $this->assertFalse((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueForBedrockStructure(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/config/application.php');
        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertTrue((new BedrockProjectType(\Mockery::mock(ComposerExecutable::class), new Filesystem()))->matchesProject($this->tempDirectory));
    }
}
