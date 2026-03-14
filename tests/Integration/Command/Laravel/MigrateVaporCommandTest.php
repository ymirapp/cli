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

namespace Ymir\Cli\Tests\Integration\Command\Laravel;

use Ymir\Cli\Command\Laravel\MigrateVaporCommand;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class MigrateVaporCommandTest extends TestCase
{
    /**
     * @var Dockerfile
     */
    private $dockerfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerfile = new Dockerfile($this->filesystem, $this->tempDir, realpath(__DIR__.'/../../../../stubs'));
    }

    public function testPerformCreatesEnvironmentDockerfilesWhenGlobalDockerfileIsDeclined(): void
    {
        $this->setupValidProject(1, 'project', [
            'staging' => ['deployment' => ['type' => 'image'], 'architecture' => 'arm64'],
            'production' => ['deployment' => ['type' => 'image'], 'architecture' => 'x86_64'],
        ], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
environments:
  staging:
    runtime: docker-arm
  production:
    runtime: docker
YAML
        );
        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', "FROM laravelphp/vapor:php82\n\nCOPY . /var/task\n");
        $this->filesystem->dumpFile($this->tempDir.'/staging.Dockerfile', "FROM laravelphp/vapor:php83\n\nCOPY . /var/task\n");
        $this->filesystem->dumpFile($this->tempDir.'/production.Dockerfile', "FROM laravelphp/vapor:php84\n\nCOPY . /var/task\n");

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME, [], ['no']);

        $this->assertStringContainsString('Created staging.Dockerfile for PHP 8.3 and arm64 architecture', $tester->getDisplay());
        $this->assertStringContainsString('Created production.Dockerfile for PHP 8.4 and x86_64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile.bak');
        $this->assertFileExists($this->tempDir.'/production.Dockerfile.bak');
        $this->assertStringContainsString('FROM --platform=linux/arm64 ymirapp/arm-php-runtime:php-83', (string) file_get_contents($this->tempDir.'/staging.Dockerfile'));
        $this->assertStringContainsString('FROM --platform=linux/amd64 ymirapp/php-runtime:php-84', (string) file_get_contents($this->tempDir.'/production.Dockerfile'));
        $this->assertFileExists($this->tempDir.'/Dockerfile');
        $this->assertFileDoesNotExist($this->tempDir.'/Dockerfile.bak');
    }

    public function testPerformCreatesEnvironmentDockerfileWhenMissingForImageDeployment(): void
    {
        $this->setupValidProject(1, 'project', ['staging' => ['deployment' => ['type' => 'image']]], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
environments:
  staging:
    runtime: docker-arm
YAML
        );

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME, [], ['no']);

        $this->assertStringContainsString('Created staging.Dockerfile for PHP 8.3 and arm64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile');
        $this->assertStringContainsString('FROM --platform=linux/arm64 ymirapp/arm-php-runtime:php-83', (string) file_get_contents($this->tempDir.'/staging.Dockerfile'));
    }

    public function testPerformCreatesGlobalDockerfileUsingProductionConfiguration(): void
    {
        $this->setupValidProject(1, 'project', [
            'staging' => ['deployment' => ['type' => 'image'], 'architecture' => 'arm64'],
            'production' => ['deployment' => ['type' => 'image'], 'architecture' => 'x86_64'],
        ], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
environments:
  staging:
    runtime: docker-arm
  production:
    runtime: docker
YAML
        );
        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', "FROM laravelphp/vapor:php82\n\nCOPY . /var/task\n");
        $this->filesystem->dumpFile($this->tempDir.'/staging.Dockerfile', "FROM laravelphp/vapor:php83\n\nCOPY . /var/task\n");
        $this->filesystem->dumpFile($this->tempDir.'/production.Dockerfile', "FROM laravelphp/vapor:php84\n\nCOPY . /var/task\n");

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME, [], ['yes']);

        $this->assertStringContainsString('Created Dockerfile for PHP 8.4 and x86_64 architecture', $tester->getDisplay());
        $this->assertStringContainsString('Using production environment configuration', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile.bak');
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile.bak');
        $this->assertFileExists($this->tempDir.'/production.Dockerfile.bak');
        $this->assertStringContainsString('FROM --platform=linux/amd64 ymirapp/php-runtime:php-84', (string) file_get_contents($this->tempDir.'/Dockerfile'));
        $this->assertFileDoesNotExist($this->tempDir.'/staging.Dockerfile');
        $this->assertFileDoesNotExist($this->tempDir.'/production.Dockerfile');
    }

    public function testPerformMigratesDockerfileForImageDeployment(): void
    {
        $this->setupValidProject(1, 'project', ['staging' => ['deployment' => ['type' => 'image'], 'architecture' => 'x86_64']], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
environments:
  staging:
    runtime: docker
YAML
        );
        $this->filesystem->dumpFile($this->tempDir.'/staging.Dockerfile', "FROM laravelphp/vapor:php84\n\nCOPY . /var/task\n");

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME, [], ['no']);

        $this->assertStringContainsString('Created staging.Dockerfile for PHP 8.4 and x86_64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile.bak');
        $this->assertStringContainsString('FROM laravelphp/vapor:php84', (string) file_get_contents($this->tempDir.'/staging.Dockerfile.bak'));
        $this->assertStringContainsString('FROM --platform=linux/amd64 ymirapp/php-runtime:php-84', (string) file_get_contents($this->tempDir.'/staging.Dockerfile'));
    }

    public function testPerformMigratesVaporConfiguration(): void
    {
        $this->setupValidProject(1, 'project', ['staging' => ['memory' => 512], 'production' => ['memory' => 1024]], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
id: 12345
name: project
environments:
  staging:
    memory: 768
    scheduler: false
  vapor-only:
    memory: 1536
YAML
        );

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME);

        $this->assertStringContainsString('Vapor configuration migrated into ymir.yml file for the following environment(s):', $tester->getDisplay());
        $this->assertStringContainsString('staging', $tester->getDisplay());

        $this->assertSame(768, $this->projectConfiguration->getEnvironmentConfiguration('staging')->toArray()['memory']);
        $this->assertFalse($this->projectConfiguration->getEnvironmentConfiguration('staging')->toArray()['cron']);
        $this->assertSame(1024, $this->projectConfiguration->getEnvironmentConfiguration('production')->toArray()['memory']);
    }

    public function testPerformShowsWarningWhenNoEnvironmentMatches(): void
    {
        $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);

        $this->filesystem->dumpFile($this->tempDir.'/vapor.yml', <<<'YAML'
environments:
  staging:
    memory: 768
YAML
        );

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $tester = $this->executeCommand(MigrateVaporCommand::NAME);

        $this->assertStringContainsString('Warning: No matching environments found between ymir.yml and vapor.yml files', $tester->getDisplay());
        $this->assertArrayNotHasKey('memory', $this->projectConfiguration->getEnvironmentConfiguration('production')->toArray());
    }

    public function testPerformThrowsExceptionIfProjectIsNotLaravel(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this command with Laravel projects');

        $this->setupValidProject(1, 'project', ['staging' => []], 'wordpress');

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $this->executeCommand(MigrateVaporCommand::NAME);
    }

    public function testPerformThrowsExceptionIfVaporConfigurationFileIsMissing(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('No vapor configuration file found at');

        $this->setupValidProject(1, 'project', ['staging' => []], 'laravel', LaravelProjectType::class);

        $this->bootApplication([new MigrateVaporCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile, $this->filesystem)]);

        $this->executeCommand(MigrateVaporCommand::NAME);
    }
}
