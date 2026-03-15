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

namespace Ymir\Cli\Tests\Unit\Laravel;

use Mockery\MockInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Laravel\VaporDockerfileMigrator;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class VaporDockerfileMigratorTest extends TestCase
{
    /**
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var VaporDockerfileMigrator
     */
    private $migrator;

    /**
     * @var string
     */
    private $projectDirectory;

    /**
     * @var MockInterface&ProjectTypeInterface
     */
    private $projectType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerfile = \Mockery::mock(Dockerfile::class);
        $this->filesystem = new Filesystem();
        $this->migrator = new VaporDockerfileMigrator($this->dockerfile, $this->filesystem);
        $this->projectDirectory = sys_get_temp_dir().'/ymir-vapor-dockerfile-migrator-'.uniqid('', true);
        $this->projectType = \Mockery::mock(ProjectTypeInterface::class);

        $this->filesystem->mkdir($this->projectDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDirectory);

        parent::tearDown();
    }

    public function testMigrateEnvironmentDockerfilesBacksUpExistingDockerfilesAndUsesResolvedPhpVersion(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging', ['architecture' => 'arm64', 'php' => '8.1']),
            $this->createEnvironmentConfiguration('production', ['php' => '8.2']),
        ]);

        $this->createLegacyDockerfile('staging', "FROM LARAVELPHP/VAPOR:php84\n\nCOPY . /var/task\n");

        $this->dockerfile->shouldReceive('create')->once()->with('arm64', '8.4', 'staging');
        $this->dockerfile->shouldReceive('create')->once()->with('x86_64', '8.2', 'production');

        $result = $this->migrator->migrateEnvironmentDockerfiles($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame([
            [
                'architecture' => 'arm64',
                'environment' => 'staging',
                'name' => 'staging.Dockerfile',
                'php_version' => '8.4',
            ],
            [
                'architecture' => 'x86_64',
                'environment' => 'production',
                'name' => 'production.Dockerfile',
                'php_version' => '8.2',
            ],
        ], $result['created_dockerfiles']);
        $this->assertSame([$this->getDockerfilePath('staging')], $result['backed_up_dockerfile_paths']);
        $this->assertFileExists($this->getDockerfilePath('staging').'.bak');
        $this->assertFileDoesNotExist($this->getDockerfilePath('staging'));
        $this->assertFileDoesNotExist($this->getDockerfilePath('production').'.bak');
    }

    public function testMigrateEnvironmentDockerfilesReturnsEmptyResultWhenNoEnvironmentConfigurationExists(): void
    {
        $this->assertSame([
            'created_dockerfiles' => [],
            'backed_up_dockerfile_paths' => [],
        ], $this->migrator->migrateEnvironmentDockerfiles(collect(), $this->projectDirectory, $this->projectType));
    }

    public function testMigrateEnvironmentDockerfilesUsesFallbackPhpVersionWhenDockerfileContentDoesNotMatchVaporPattern(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging'),
        ]);

        $this->createLegacyDockerfile('staging', "FROM php:8.4-cli\n\nCOPY . /var/task\n");

        $this->projectType->shouldReceive('getDefaultPhpVersion')->once()->andReturn('8.0');
        $this->dockerfile->shouldReceive('create')->once()->with('x86_64', '8.0', 'staging');

        $result = $this->migrator->migrateEnvironmentDockerfiles($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame('8.0', $result['created_dockerfiles'][0]['php_version']);
        $this->assertSame([$this->getDockerfilePath('staging')], $result['backed_up_dockerfile_paths']);
    }

    public function testMigrateEnvironmentDockerfilesUsesProjectDefaultPhpVersionAndDefaultArchitecture(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging'),
        ]);

        $this->projectType->shouldReceive('getDefaultPhpVersion')->once()->andReturn('8.3');
        $this->dockerfile->shouldReceive('create')->once()->with('x86_64', '8.3', 'staging');

        $result = $this->migrator->migrateEnvironmentDockerfiles($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame([
            [
                'architecture' => 'x86_64',
                'environment' => 'staging',
                'name' => 'staging.Dockerfile',
                'php_version' => '8.3',
            ],
        ], $result['created_dockerfiles']);
        $this->assertSame([], $result['backed_up_dockerfile_paths']);
    }

    public function testMigrateGlobalDockerfileReturnsEmptyResultWhenNoEnvironmentConfigurationExists(): void
    {
        $this->assertSame([
            'created_dockerfiles' => [],
            'backed_up_dockerfile_paths' => [],
        ], $this->migrator->migrateGlobalDockerfile(collect(), $this->projectDirectory, $this->projectType));
    }

    public function testMigrateGlobalDockerfileUsesFirstEnvironmentAsSourceWhenProductionIsMissing(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging', ['architecture' => 'arm64', 'php' => '8.1']),
            $this->createEnvironmentConfiguration('qa', ['architecture' => 'x86_64', 'php' => '8.2']),
        ]);

        $this->createLegacyDockerfile('staging', "FROM laravelphp/vapor:php83\n\nCOPY . /var/task\n");

        $this->dockerfile->shouldReceive('create')->once()->with('arm64', '8.3', '');

        $result = $this->migrator->migrateGlobalDockerfile($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame('8.3', $result['created_dockerfiles'][0]['php_version']);
        $this->assertSame([$this->getDockerfilePath('staging')], $result['backed_up_dockerfile_paths']);
    }

    public function testMigrateGlobalDockerfileUsesProductionEnvironmentAsSourceWhenAvailable(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging', ['architecture' => 'arm64', 'php' => '8.1']),
            $this->createEnvironmentConfiguration('production', ['architecture' => 'x86_64', 'php' => '8.2']),
        ]);

        $this->createLegacyDockerfile('', "FROM laravelphp/vapor:php82\n\nCOPY . /var/task\n");
        $this->createLegacyDockerfile('staging', "FROM laravelphp/vapor:php83\n\nCOPY . /var/task\n");
        $this->createLegacyDockerfile('production', "FROM laravelphp/vapor:php84\n\nCOPY . /var/task\n");

        $this->dockerfile->shouldReceive('create')->once()->with('x86_64', '8.4', '');

        $result = $this->migrator->migrateGlobalDockerfile($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame([
            [
                'architecture' => 'x86_64',
                'environment' => '',
                'name' => 'Dockerfile',
                'php_version' => '8.4',
            ],
        ], $result['created_dockerfiles']);
        $this->assertSame([
            $this->getDockerfilePath('staging'),
            $this->getDockerfilePath('production'),
            $this->getDockerfilePath(''),
        ], $result['backed_up_dockerfile_paths']);
    }

    public function testMigrateGlobalDockerfileUsesProjectDefaultPhpVersionWhenSourceDockerfileIsMissing(): void
    {
        $environmentConfigurations = collect([
            $this->createEnvironmentConfiguration('staging'),
        ]);

        $this->projectType->shouldReceive('getDefaultPhpVersion')->once()->andReturn('8.4');
        $this->dockerfile->shouldReceive('create')->once()->with('x86_64', '8.4', '');

        $result = $this->migrator->migrateGlobalDockerfile($environmentConfigurations, $this->projectDirectory, $this->projectType);

        $this->assertSame('8.4', $result['created_dockerfiles'][0]['php_version']);
        $this->assertSame([], $result['backed_up_dockerfile_paths']);
    }

    private function createEnvironmentConfiguration(string $name, array $configuration = []): EnvironmentConfiguration
    {
        return new EnvironmentConfiguration($name, $configuration);
    }

    private function createLegacyDockerfile(string $environment, string $content): void
    {
        $this->filesystem->dumpFile($this->getDockerfilePath($environment), $content);
    }

    private function getDockerfilePath(string $environment = ''): string
    {
        return sprintf('%s/%s', $this->projectDirectory, Dockerfile::getFileName($environment));
    }
}
