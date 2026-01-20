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

    public function testCreateDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no']); // No specific environment, no configure project

        $this->assertStringContainsString('Dockerfile created', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');
    }

    public function testCreateDockerfileAndConfigureProjectWithOption(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'project', ['staging' => []]);

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['--configure-project' => true], ['no']); // No specific environment

        $this->assertStringContainsString('Dockerfile created', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/Dockerfile');

        $config = $this->projectConfiguration->getEnvironmentConfiguration('staging');
        $this->assertTrue($config->isImageDeploymentType());
    }

    public function testCreateDockerfileForEnvironment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'project' => $project]);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, ['environment' => 'staging'], ['no']); // no configure project

        $this->assertStringContainsString('Dockerfile created for "staging" environment', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/staging.Dockerfile');
    }

    public function testDoNotOverwriteExistingDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', 'old content');

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no', 'no', 'no']); // No specific environment, overwrite no, no configure project

        $this->assertStringNotContainsString('Dockerfile created', $tester->getDisplay());
        $this->assertStringEqualsFile($this->tempDir.'/Dockerfile', 'old content');
    }

    public function testOverwriteExistingDockerfile(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject();

        $this->filesystem->dumpFile($this->tempDir.'/Dockerfile', 'old content');

        $this->bootApplication([new CreateDockerfileCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(CreateDockerfileCommand::NAME, [], ['no', 'yes', 'no']); // No specific environment, overwrite yes, no configure project

        $this->assertStringContainsString('Dockerfile created', $tester->getDisplay());
        $this->assertStringNotEqualsFile($this->tempDir.'/Dockerfile', 'old content');
    }
}
