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
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Tests\Mock\FilesystemMockTrait;
use Ymir\Cli\Tests\Mock\GitHubClientMockTrait;
use Ymir\Cli\Tests\Mock\WpCliExecutableMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Type\WordPressProjectType
 */
class WordPressProjectTypeTest extends TestCase
{
    use FilesystemMockTrait;
    use GitHubClientMockTrait;
    use WpCliExecutableMockTrait;

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
            ['wp-admin/included.txt'],
            ['wp-includes/included.txt'],
        ];
    }

    /**
     * @dataProvider provideRequiredFileTypes
     */
    public function testGetBuildFilesIncludesRequiredFileTypes(string $filename): void
    {
        $requiredFile = $this->tempDirectory.'/'.$filename;

        touch($this->tempDirectory.'/excluded.txt');
        touch($requiredFile);

        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesRequiredPluginFiles(): void
    {
        mkdir($this->tempDirectory.'/wp-content/plugins/foo', 0777, true);

        $blockJsonPath = $this->tempDirectory.'/wp-content/plugins/foo/block.json';

        touch($this->tempDirectory.'/excluded.txt');
        touch($blockJsonPath);

        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($blockJsonPath, $files[0]->getPathname());
    }

    /**
     * @dataProvider provideRequiredThemeFiles
     */
    public function testGetBuildFilesIncludesRequiredThemeFiles(string $filename): void
    {
        $themeDirectory = $this->tempDirectory.'/wp-content/themes/foo';

        mkdir($themeDirectory, 0777, true);

        $requiredThemeFile = $themeDirectory.'/'.$filename;

        touch($themeDirectory.'/excluded.txt');
        touch($requiredThemeFile);

        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

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

        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesWpCliYml(): void
    {
        $wpCliYmlPath = $this->tempDirectory.'/wp-cli.yml';

        touch($this->tempDirectory.'/excluded.txt');
        touch($wpCliYmlPath);

        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

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
            Build\ModifyWordPressConfigurationStep::class,
            Build\ExtractAssetFilesStep::class,
        ], (new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->getBuildSteps());
    }

    public function testGetInstallationMessage()
    {
        $this->assertSame('Downloading WordPress using WP-CLI', (new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->getInstallationMessage());
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'/wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame('wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath());
    }

    public function testGetName()
    {
        $this->assertSame('WordPress', (new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->getName());
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'wp-content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'/wp-content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame('wp-content/plugins', $projectType->getPluginsDirectoryPath());
    }

    public function testGetProjectFilesExcludesUploadsDirectory()
    {
        mkdir($this->tempDirectory.'/wp-content/uploads', 0777, true);

        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/wp-content/uploads/excluded.txt');

        $projectType = new WordPressProjectType(new Filesystem(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetProjectFilesIncludesWpConfigEvenIfItsInGitignore()
    {
        $wpConfigFilePath = $this->tempDirectory.'/wp-config.php';

        touch($wpConfigFilePath);
        file_put_contents($this->tempDirectory.'/.gitignore', 'wp-config.php');

        $projectType = new WordPressProjectType(new Filesystem(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($wpConfigFilePath, $files[0]->getPathname());
    }

    public function testGetSlug()
    {
        $this->assertSame('wordpress', (new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->getSlug());
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'wp-content/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame($buildDir.'/wp-content/uploads', $projectType->getUploadsDirectoryPath($buildDir));
    }

    public function testGetUploadsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock());

        $this->assertSame('wp-content/uploads', $projectType->getUploadsDirectoryPath());
    }

    public function testInstallProject()
    {
        $wpCliExecutable = $this->getWpCliExecutableMock();

        $wpCliExecutable->expects($this->once())
                        ->method('downloadWordPress')
                        ->with($this->identicalTo('/path/to/project'));

        (new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $wpCliExecutable))->installProject('/path/to/project');
    }

    public function testIsEligibleForInstallationReturnsFalseWhenWpCliReturnsAWordPressVersion(): void
    {
        $wpCliExecutable = $this->getWpCliExecutableMock();

        $wpCliExecutable->expects($this->once())
                        ->method('getVersion')
                        ->with($this->identicalTo($this->tempDirectory))
                        ->willReturn('version');

        $this->assertFalse((new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsFalseWhenWpCliThrowsException(): void
    {
        $wpCliExecutable = $this->getWpCliExecutableMock();

        $wpCliExecutable->expects($this->once())
                        ->method('getVersion')
                        ->willThrowException(new \Exception());

        $this->assertFalse((new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsTrueWhenWpCliFailsToReturnAWordPressVersion(): void
    {
        $wpCliExecutable = $this->getWpCliExecutableMock();

        $wpCliExecutable->expects($this->once())
                        ->method('getVersion')
                        ->with($this->identicalTo($this->tempDirectory))
                        ->willReturn(null);

        $this->assertTrue((new WordPressProjectType($this->getFilesystemMock(), $this->getGitHubClientMock(), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpConfigIsMissing(): void
    {
        $this->assertFalse((new WordPressProjectType(new Filesystem(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueWhenWpConfigIsPresent(): void
    {
        touch($this->tempDirectory.'/wp-config.php');

        $this->assertTrue((new WordPressProjectType(new Filesystem(), $this->getGitHubClientMock(), $this->getWpCliExecutableMock()))->matchesProject($this->tempDirectory));
    }
}
