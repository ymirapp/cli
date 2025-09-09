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

namespace Ymir\Cli\Tests\Integration\Command\Environment;

use Ymir\Cli\Command\Environment\UploadEnvironmentVariablesCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class UploadEnvironmentVariablesCommandTest extends TestCase
{
    public function testUploadEnvironmentVariables(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->filesystem->dumpFile($this->tempDir.'/.env.staging', "FOO=BAR\nBAZ=QUX");

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->with($project, $environment, ['FOO' => 'BAR', 'BAZ' => 'QUX'], true)->once();

        $this->bootApplication([new UploadEnvironmentVariablesCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->filesystem)]);

        $tester = $this->executeCommand(UploadEnvironmentVariablesCommand::NAME, ['environment' => 'staging'], ['yes', 'no']);

        $this->assertStringContainsString('Environment variables uploaded', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/.env.staging');
    }

    public function testUploadEnvironmentVariablesAndDeletesFile(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->filesystem->dumpFile($this->tempDir.'/.env.staging', 'FOO=BAR');

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->with($project, $environment, ['FOO' => 'BAR'], true)->once();

        $this->bootApplication([new UploadEnvironmentVariablesCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->filesystem)]);

        $tester = $this->executeCommand(UploadEnvironmentVariablesCommand::NAME, ['environment' => 'staging'], ['yes', 'yes']);

        $this->assertStringContainsString('Environment variables uploaded', $tester->getDisplay());
        $this->assertFileDoesNotExist($this->tempDir.'/.env.staging');
    }

    public function testUploadEnvironmentVariablesInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->filesystem->dumpFile($this->tempDir.'/.env.staging', 'FOO=BAR');

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->with($project, $environment, ['FOO' => 'BAR'], true)->once();

        $this->bootApplication([new UploadEnvironmentVariablesCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->filesystem)]);

        $tester = $this->executeCommand(UploadEnvironmentVariablesCommand::NAME, [], ['staging', 'yes', 'no']);

        $this->assertStringContainsString('Environment variables uploaded', $tester->getDisplay());
    }
}
