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
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\GitHubClient;
use Ymir\Cli\Project\Build;
use Ymir\Cli\Project\Initialization;
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Tests\TestCase;

class WordPressProjectTypeTest extends TestCase
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
            ['wp-admin/included.txt'],
            ['wp-includes/included.txt'],
        ];
    }

    public function testGenerateEnvironmentConfigurationForProduction(): void
    {
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
        ], $projectType->generateEnvironmentConfiguration('production', ['foo' => 'bar'])->toArray());
    }

    public function testGenerateEnvironmentConfigurationForStaging(): void
    {
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame([
            'architecture' => 'arm64',
            'gateway' => false,
            'foo' => 'bar',
            'cron' => false,
            'warmup' => false,
        ], $projectType->generateEnvironmentConfiguration('staging', ['foo' => 'bar'])->toArray());
    }

    /**
     * @dataProvider provideRequiredFileTypes
     */
    public function testGetBuildFilesIncludesRequiredFileTypes(string $filename): void
    {
        $requiredFile = $this->tempDirectory.'/'.$filename;

        touch($this->tempDirectory.'/excluded.txt');
        touch($requiredFile);

        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

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

        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

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

        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

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

        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $files = iterator_to_array($projectType->getBuildFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($requiredFile, $files[0]->getPathname());
    }

    public function testGetBuildFilesIncludesWpCliYml(): void
    {
        $wpCliYmlPath = $this->tempDirectory.'/wp-cli.yml';

        touch($this->tempDirectory.'/excluded.txt');
        touch($wpCliYmlPath);

        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

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
            Build\ModifyWordPressConfigurationStep::class,
            Build\ExtractAssetFilesStep::class,
        ], (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getBuildSteps());
    }

    public function testGetInitializationSteps(): void
    {
        $this->assertSame([
            Initialization\DatabaseInitializationStep::class,
            Initialization\CacheInitializationStep::class,
            Initialization\DockerInitializationStep::class,
            Initialization\IntegrationInitializationStep::class,
            Initialization\WordPressConfigurationInitializationStep::class,
        ], (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getInitializationSteps());
    }

    public function testGetInstallationMessage(): void
    {
        $this->assertSame('Downloading WordPress using WP-CLI', (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getInstallationMessage());
    }

    public function testGetMediaDirectoryName(): void
    {
        $this->assertSame('uploads', (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getMediaDirectoryName());
    }

    public function testGetMediaDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'wp-content/uploads', $projectType->getMediaDirectoryPath($buildDir));
    }

    public function testGetMediaDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'/wp-content/uploads', $projectType->getMediaDirectoryPath($buildDir));
    }

    public function testGetMediaDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame('wp-content/uploads', $projectType->getMediaDirectoryPath());
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'/wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath($buildDir));
    }

    public function testGetMustUsePluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame('wp-content/mu-plugins', $projectType->getMustUsePluginsDirectoryPath());
    }

    public function testGetName(): void
    {
        $this->assertSame('WordPress', (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getName());
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryEndingWithSlash(): void
    {
        $buildDir = '/path/to/build/';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'wp-content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithBaseDirectoryNotEndingWithSlash(): void
    {
        $buildDir = '/path/to/build';
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame($buildDir.'/wp-content/plugins', $projectType->getPluginsDirectoryPath($buildDir));
    }

    public function testGetPluginsDirectoryPathWithoutBasePathReturnsRelativePath(): void
    {
        $projectType = new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $this->assertSame('wp-content/plugins', $projectType->getPluginsDirectoryPath());
    }

    public function testGetProjectFilesExcludesUploadsDirectory(): void
    {
        mkdir($this->tempDirectory.'/wp-content/uploads', 0777, true);

        $keepFilePath = $this->tempDirectory.'/keep.txt';

        touch($keepFilePath);
        touch($this->tempDirectory.'/wp-content/uploads/excluded.txt');

        $projectType = new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($keepFilePath, $files[0]->getPathname());
    }

    public function testGetProjectFilesIncludesWpConfigEvenIfItsInGitignore(): void
    {
        $wpConfigFilePath = $this->tempDirectory.'/wp-config.php';

        touch($wpConfigFilePath);
        file_put_contents($this->tempDirectory.'/.gitignore', 'wp-config.php');

        $projectType = new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class));

        $files = iterator_to_array($projectType->getProjectFiles($this->tempDirectory), false);

        $this->assertCount(1, $files);

        $this->assertSame($wpConfigFilePath, $files[0]->getPathname());
    }

    public function testGetSlug(): void
    {
        $this->assertSame('wordpress', (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->getSlug());
    }

    public function testInstallIntegration(): void
    {
        $gitHubClient = \Mockery::mock(GitHubClient::class);
        $zipArchive = \Mockery::mock(\ZipArchive::class);

        $gitHubClient->shouldReceive('downloadLatestVersion')->once()
                     ->with('ymirapp/wordpress-plugin')
                     ->andReturn($zipArchive);

        $zipArchive->shouldReceive('extractTo')->once()
                   ->with($this->tempDirectory.'/wp-content/plugins')
                   ->andReturnUsing(function ($directory) {
                       mkdir($directory.'/ymirapp-wordpress-plugin-1.0.0', 0777, true);

                       return true;
                   });

        (new WordPressProjectType(new Filesystem(), $gitHubClient, \Mockery::mock(WpCliExecutable::class)))->installIntegration($this->tempDirectory);

        $this->assertDirectoryExists($this->tempDirectory.'/wp-content/plugins/ymir-wordpress-plugin');
    }

    public function testInstallProject(): void
    {
        $wpCliExecutable = \Mockery::mock(WpCliExecutable::class);

        $wpCliExecutable->shouldReceive('downloadWordPress')->once()
                        ->with('/path/to/project');

        (new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), $wpCliExecutable))->installProject('/path/to/project');
    }

    public function testIsEligibleForInstallationReturnsFalseWhenWpCliReturnsAWordPressVersion(): void
    {
        $wpCliExecutable = \Mockery::mock(WpCliExecutable::class);

        $wpCliExecutable->shouldReceive('getVersion')->once()
                        ->with($this->tempDirectory)
                        ->andReturn('version');

        $this->assertFalse((new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsFalseWhenWpCliThrowsException(): void
    {
        $wpCliExecutable = \Mockery::mock(WpCliExecutable::class);

        $wpCliExecutable->shouldReceive('getVersion')->once()
                        ->andThrow(new \Exception());

        $this->assertFalse((new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsEligibleForInstallationReturnsTrueWhenWpCliFailsToReturnAWordPressVersion(): void
    {
        $wpCliExecutable = \Mockery::mock(WpCliExecutable::class);

        $wpCliExecutable->shouldReceive('getVersion')->once()
                        ->with($this->tempDirectory)
                        ->andReturn(null);

        $this->assertTrue((new WordPressProjectType(\Mockery::mock(Filesystem::class), \Mockery::mock(GitHubClient::class), $wpCliExecutable))->isEligibleForInstallation($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenPluginsDirectoryIsMissing(): void
    {
        $this->assertFalse((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsFalseWhenYmirPhpIsMissing(): void
    {
        mkdir($this->tempDirectory.'/wp-content/plugins/ymir-wordpress-plugin', 0777, true);

        $this->assertFalse((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testIsIntegrationInstalledReturnsTrueWhenYmirPhpIsPresent(): void
    {
        mkdir($this->tempDirectory.'/wp-content/plugins/ymir-wordpress-plugin', 0777, true);
        file_put_contents($this->tempDirectory.'/wp-content/plugins/ymir-wordpress-plugin/ymir.php', "<?php\n/**\n * Plugin Name: Ymir\n */");

        $this->assertTrue((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->isIntegrationInstalled($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpAdminIsMissing(): void
    {
        mkdir($this->tempDirectory.'/wp-content', 0777, true);
        touch($this->tempDirectory.'/wp-config-sample.php');

        $this->assertFalse((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpConfigSampleIsMissing(): void
    {
        mkdir($this->tempDirectory.'/wp-admin', 0777, true);
        mkdir($this->tempDirectory.'/wp-content', 0777, true);

        $this->assertFalse((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsFalseWhenWpContentIsMissing(): void
    {
        mkdir($this->tempDirectory.'/wp-admin', 0777, true);
        touch($this->tempDirectory.'/wp-config-sample.php');

        $this->assertFalse((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->matchesProject($this->tempDirectory));
    }

    public function testMatchesProjectReturnsTrueForWordPressStructure(): void
    {
        mkdir($this->tempDirectory.'/wp-admin', 0777, true);
        mkdir($this->tempDirectory.'/wp-content', 0777, true);
        touch($this->tempDirectory.'/wp-config-sample.php');

        $this->assertTrue((new WordPressProjectType(new Filesystem(), \Mockery::mock(GitHubClient::class), \Mockery::mock(WpCliExecutable::class)))->matchesProject($this->tempDirectory));
    }
}
