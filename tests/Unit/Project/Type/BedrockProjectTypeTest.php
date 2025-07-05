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
use Ymir\Cli\Build;
use Ymir\Cli\Project\Type\BedrockProjectType;
use Ymir\Cli\Tests\Mock\ComposerExecutableMockTrait;
use Ymir\Cli\Tests\Mock\FilesystemMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Type\BedrockProjectType
 */
class BedrockProjectTypeTest extends TestCase
{
    use ComposerExecutableMockTrait;
    use FilesystemMockTrait;

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

    public function testGetAssetFilesExcludesFilesBelowWebDirectory(): void
    {
        mkdir($this->tempDirectory.'/web', 0777, true);

        $keepFilePath = $this->tempDirectory.'/web/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/excluded.txt');

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetBuildFilesExcludesWpContent(): void
    {
        $wpContentDirectory = $this->tempDirectory.'/web/wp/wp-content';

        mkdir($wpContentDirectory, 0777, true);

        touch($wpContentDirectory.'/excluded.txt');

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesWpCliYml(): void
    {
        $wpCliYmlPath = $this->tempDirectory.'/wp-cli.yml';

        touch($this->tempDirectory.'/excluded.txt');
        touch($wpCliYmlPath);

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($wpCliYmlPath, $files[0]->getPathname());
    }

    public function testGetBuildSteps()
    {
        $this->assertSame([
            Build\CopyProjectFilesStep::class,
            Build\DownloadWpCliStep::class,
            Build\ExecuteBuildCommandsStep::class,
            Build\EnsureIntegrationIsInstalledStep::class,
            Build\CopyMustUsePluginStep::class,
            Build\ExtractAssetFilesStep::class,
        ], (new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getBuildSteps());
    }

    public function testGetEnvironmentConfigurationForProduction(): void
    {
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev',
            ],
        ], $projectType->getEnvironmentConfiguration('production', ['foo' => 'bar']));
    }

    public function testGetEnvironmentConfigurationForStaging(): void
    {
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
            'cdn' => [
                'caching' => 'assets',
            ],
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install',
            ],
        ], $projectType->getEnvironmentConfiguration('staging', ['foo' => 'bar']));
    }

    public function testGetInstallationMessage()
    {
        $this->assertSame('Creating new Bedrock project using Composer', (new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getInstallationMessage());
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('web/app/mu-plugins', $projectType->getMustUsePluginsDirectoryPath());
    }

    public function testGetName()
    {
        $this->assertSame('Bedrock', (new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getName());
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'web/app/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/web/app/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('web/app/plugins', $projectType->getPluginsDirectoryPath());
    }

    public function testGetProjectFilesExcludesUploadsDirectory()
    {
        mkdir($this->tempDirectory.'/web/app/uploads', 0777, true);

        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/web/app/uploads/excluded.txt');

        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetSlug()
    {
        $this->assertSame('bedrock', (new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getSlug());
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'web/app/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/web/app/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('web/app/uploads', $projectType->getUploadsDirectoryPath());
    }

    public function testInstallIntegration()
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->once())
                           ->method('require')
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo('/path/to/project'));

        (new BedrockProjectType($composerExecutable, $this->getFilesystemMock()))->installIntegration('/path/to/project');
    }

    public function testInstallProject()
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->once())
                           ->method('createProject')
                           ->with($this->identicalTo('roots/bedrock'), $this->identicalTo('/path/to/project'));

        (new BedrockProjectType($composerExecutable, $this->getFilesystemMock()))->installProject('/path/to/project');
    }

    public function testIsEligibleForInstallationReturnsFalseWhenDirectoryIsNotEmpty(): void
    {
        touch($this->tempDirectory.'/dummy.txt');

        $this->assertFalse((new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsTrueWhenDirectoryIsEmpty(): void
    {
        $this->assertTrue((new BedrockProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenWordPressPluginIsNotInstalled(): void
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->once())
                           ->method('isPackageInstalled')
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory))
                           ->willReturn(false);

        $this->assertFalse((new BedrockProjectType($composerExecutable, $this->getFilesystemMock()))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsTrueWhenWordPressPluginIsInstalled(): void
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->once())
                           ->method('isPackageInstalled')
                           ->with($this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory))
                           ->willReturn(true);

        $this->assertTrue((new BedrockProjectType($composerExecutable, $this->getFilesystemMock()))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenAppDirectoryIsMissing(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web', 0777, true);

        touch($this->tempDirectory.'/config/application.php');
        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertFalse((new BedrockProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenApplicationConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertFalse((new BedrockProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/config/application.php');

        $this->assertFalse((new BedrockProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueForBedrockStructure(): void
    {
        mkdir($this->tempDirectory.'/config', 0777, true);
        mkdir($this->tempDirectory.'/web/app', 0777, true);

        touch($this->tempDirectory.'/config/application.php');
        touch($this->tempDirectory.'/web/wp-config.php');

        $this->assertTrue((new BedrockProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }
}
