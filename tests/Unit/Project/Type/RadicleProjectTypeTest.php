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
use Ymir\Cli\Project\Type\RadicleProjectType;
use Ymir\Cli\Tests\Mock\ComposerExecutableMockTrait;
use Ymir\Cli\Tests\Mock\FilesystemMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Type\RadicleProjectType
 */
class RadicleProjectTypeTest extends TestCase
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
            ['public/wp/wp-admin/included.txt'],
            ['public/wp/wp-includes/included.txt'],
        ];
    }

    public function testGetAssetFilesExcludesFilesBelowPublicDirectory(): void
    {
        mkdir($this->tempDirectory.'/public', 0777, true);

        $keepFilePath = $this->tempDirectory.'/public/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/excluded.txt');

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetAssetFilesExcludesFilesInWpContent(): void
    {
        mkdir($this->tempDirectory.'/public/wp/wp-content', 0777, true);

        $keepFilePath = $this->tempDirectory.'/public/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/public/wp/wp-content/excluded.txt');

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getAssetFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetBuildFilesExcludesWpContent(): void
    {
        $wpContentDirectory = $this->tempDirectory.'/public/wp/wp-content';

        mkdir($wpContentDirectory, 0777, true);

        touch($wpContentDirectory.'/excluded.txt');

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesRequiredPluginFiles(): void
    {
        mkdir($this->tempDirectory.'/public/content/plugins/foo', 0777, true);

        $blockJsonPath = $this->tempDirectory.'/public/content/plugins/foo/block.json';

        touch($this->tempDirectory.'/excluded.txt');
        touch($blockJsonPath);

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($blockJsonPath, $files[0]->getPathname());
    }

    /**
     * @dataProvider provideRequiredThemeFiles
     */
    public function testGetBuildFilesIncludesRequiredThemeFiles(string $filename): void
    {
        $themeDirectory = $this->tempDirectory.'/public/content/themes/foo';

        mkdir($themeDirectory, 0777, true);

        $requiredThemeFile = $themeDirectory.'/'.$filename;

        touch($themeDirectory.'/excluded.txt');
        touch($requiredThemeFile);

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesWpCliYml(): void
    {
        $wpCliYmlPath = $this->tempDirectory.'/wp-cli.yml';

        touch($this->tempDirectory.'/excluded.txt');
        touch($wpCliYmlPath);

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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
        ], (new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getBuildSteps());
    }

    public function testGetEnvironmentConfigurationForProduction(): void
    {
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame([
            'architecture' => 'arm64',
            'foo' => 'bar',
            'build' => [
                'COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev',
                'yarn install && yarn build && rm -rf node_modules',
            ],
        ], $projectType->getEnvironmentConfiguration('production', ['foo' => 'bar']));
    }

    public function testGetEnvironmentConfigurationForStaging(): void
    {
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

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
                'yarn install && yarn build && rm -rf node_modules',
            ],
        ], $projectType->getEnvironmentConfiguration('staging', ['foo' => 'bar']));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'public/content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/public/content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('public/content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath());
    }

    public function testGetName()
    {
        $this->assertSame('Radicle', (new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getName());
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'public/content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/public/content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('public/content/plugins', $projectType->getPluginsDirectoryPath());
    }

    public function testGetProjectFilesExcludesUploadsDirectory()
    {
        mkdir($this->tempDirectory.'/public/content/uploads', 0777, true);

        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/public/content/uploads/excluded.txt');

        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetSlug()
    {
        $this->assertSame('radicle', (new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock()))->getSlug());
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'public/content/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame($buildDir.'/public/content/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new RadicleProjectType($this->getComposerExecutableMock(), $this->getFilesystemMock());

        $this->assertSame('public/content/uploads', $projectType->getUploadsDirectoryPath());
    }

    public function testInstallIntegration()
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->exactly(2))
                           ->method('require')
                           ->withConsecutive(
                               [$this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo('/path/to/project')],
                               [$this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo('/path/to/project')]
                           );

        (new RadicleProjectType($composerExecutable, $this->getFilesystemMock()))->installIntegration('/path/to/project');
    }

    public function testIsIntegrationInstalledReturnsFalseWhenLaravelBridgeIsMissing(): void
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->once())
                            ->method('isPackageInstalled')
                            ->with($this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo($this->tempDirectory))
                            ->willReturn(false);

        $this->assertFalse((new RadicleProjectType($composerExecutable, $this->getFilesystemMock()))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenWordPressPluginIsMissing(): void
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->exactly(2))
                           ->method('isPackageInstalled')
                           ->withConsecutive(
                               [$this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo($this->tempDirectory)],
                               [$this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory)]
                           )
                           ->willReturnOnConsecutiveCalls(true, false);

        $this->assertFalse((new RadicleProjectType($composerExecutable, $this->getFilesystemMock()))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsTrueWhenLaravelBridgeAndWordPressPluginAreInstalled(): void
    {
        $composerExecutable = $this->getComposerExecutableMock();

        $composerExecutable->expects($this->exactly(2))
                           ->method('isPackageInstalled')
                           ->withConsecutive(
                               [$this->identicalTo('ymirapp/laravel-bridge'), $this->identicalTo($this->tempDirectory)],
                               [$this->identicalTo('ymirapp/wordpress-plugin'), $this->identicalTo($this->tempDirectory)]
                           )
                           ->willReturn(true);

        $this->assertTrue((new RadicleProjectType($composerExecutable, $this->getFilesystemMock()))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenAppDirectoryIsMissing(): void
    {
        mkdir($this->tempDirectory.'/bedrock', 0777, true);
        mkdir($this->tempDirectory.'/public', 0777, true);

        touch($this->tempDirectory.'/bedrock/application.php');
        touch($this->tempDirectory.'/public/wp-config.php');

        $this->assertFalse((new RadicleProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenApplicationConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/public/content', 0777, true);

        touch($this->tempDirectory.'/public/wp-config.php');

        $this->assertFalse((new RadicleProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpConfigIsMissing(): void
    {
        mkdir($this->tempDirectory.'/bedrock', 0777, true);
        mkdir($this->tempDirectory.'/public/content', 0777, true);

        touch($this->tempDirectory.'/bedrock/application.php');

        $this->assertFalse((new RadicleProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueForRadicleStructure(): void
    {
        mkdir($this->tempDirectory.'/bedrock', 0777, true);
        mkdir($this->tempDirectory.'/public/content', 0777, true);

        touch($this->tempDirectory.'/bedrock/application.php');
        touch($this->tempDirectory.'/public/wp-config.php');

        $this->assertTrue((new RadicleProjectType($this->getComposerExecutableMock(), new Filesystem()))->matchesProject($this->tempDirectory));
    }
}
