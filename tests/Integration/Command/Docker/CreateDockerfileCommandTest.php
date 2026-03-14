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

namespace Ymir\Cli\Tests\Integration\Command\Docker;

use Ymir\Cli\Command\Docker\CreateDockerfileCommand;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateDockerfileCommandTest extends TestCase
{
    /**
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerfile = new Dockerfile($this->filesystem, $this->tempDir, realpath(__DIR__.'/../../../../stubs'));
    }

    public static function provideProjectTypeDefaultPhpVersions(): array
    {
        return [
            ['laravel', 'php-83'],
            ['wordpress', 'php-82'],
            ['bedrock', 'php-82'],
            ['radicle', 'php-82'],
        ];
    }

    public function testCreateDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'project', ['production' => ['architecture' => 'x86_64', 'php' => '8.3']]);

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no']); // no configure project

        $this->assertStringContainsString('Created Dockerfile for PHP 8.2 and arm64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');
        $this->assertStringContainsString('FROM --platform=linux/arm64 ymirapp/arm-php-runtime:php-82', (string) file_get_contents($this->tempDir.'/Dockerfile'));
    }

    public function testCreateDockerfileAndConfigureProjectWithOption(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'project', ['staging' => []]);

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['--configure-project' => true], []);

        $this->assertStringContainsString('Created Dockerfile for PHP 8.2 and arm64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');

        $config = $this->projectConfiguration->getEnvironmentConfiguration('staging');
        $this->assertTrue($config->isImageDeploymentType());
    }

    public function testCreateDockerfileForEnvironment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => ['architecture' => 'x86_64', 'php' => '8.1']]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'project' => $project]);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['environment' => 'staging'], ['no']); // no configure project

        $this->assertStringContainsString('Created staging.Dockerfile for PHP 8.1 and x86_64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile');
        $this->assertStringContainsString('FROM --platform=linux/amd64 ymirapp/php-runtime:php-81', (string) file_get_contents($this->tempDir.'/staging.Dockerfile'));
    }

    /**
     * @dataProvider provideProjectTypeDefaultPhpVersions
     */
    public function testCreateDockerfileUsesProjectTypeDefaultPhpVersion(string $projectType, string $phpTag): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'project', ['production' => []], $projectType);

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no']);

        $this->assertFileExists($this->tempDir.'/Dockerfile');
        $this->assertStringContainsString(sprintf('FROM --platform=linux/arm64 ymirapp/arm-php-runtime:%s', $phpTag), (string) file_get_contents($this->tempDir.'/Dockerfile'));
    }

    public function testCreateDockerfileWithArchitectureOption(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['--architecture' => 'x86_64'], ['no']);

        $this->assertStringContainsString('Created Dockerfile for PHP 8.2 and x86_64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');
        $this->assertStringContainsString('FROM --platform=linux/amd64 ymirapp/php-runtime:php-82', (string) file_get_contents($this->tempDir.'/Dockerfile'));
    }

    public function testCreateDockerfileWithPhpOption(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['--php' => '8.3'], ['no']);

        $this->assertStringContainsString('Created Dockerfile for PHP 8.3 and arm64 architecture', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');
        $this->assertStringContainsString('FROM --platform=linux/arm64 ymirapp/arm-php-runtime:php-83', (string) file_get_contents($this->tempDir.'/Dockerfile'));
    }

    public function testDoNotOverwriteExistingDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', 'old content');

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no', 'no']); // overwrite no, no configure project

        $this->assertStringNotContainsString('Dockerfile created', $tester->getDisplay());
        $this->assertStringEqualsFile($this->tempDir.'/Dockerfile', 'old content');
    }

    public function testOverwriteExistingDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', 'old content');

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['yes', 'no']); // overwrite yes, no configure project

        $this->assertStringContainsString('Created Dockerfile for PHP 8.2 and arm64 architecture', $tester->getDisplay());
        $this->assertStringNotEqualsFile($this->tempDir.'/Dockerfile', 'old content');
    }
}
