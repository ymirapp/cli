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

use Ymir\Cli\Command\Environment\DownloadEnvironmentVariablesCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DownloadEnvironmentVariablesCommandTest extends TestCase
{
    public function testDownloadEnvironmentVariables(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentVariables')->with($project, $environment)->andReturn(collect(['FOO' => 'BAR', 'BAZ' => 'QUX']));

        $this->bootApplication([new DownloadEnvironmentVariablesCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->filesystem)]);

        $tester = $this->executeCommand(DownloadEnvironmentVariablesCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Environment variables downloaded to', $tester->getDisplay());
        $this->assertStringContainsString('.env.staging', $tester->getDisplay());
        $this->assertStringEqualsFile($this->tempDir.'/.env.staging', "BAZ=QUX\nFOO=BAR");
    }

    public function testDownloadEnvironmentVariablesInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentVariables')->with($project, $environment)->andReturn(collect(['FOO' => 'BAR']));

        $this->bootApplication([new DownloadEnvironmentVariablesCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->filesystem)]);

        $tester = $this->executeCommand(DownloadEnvironmentVariablesCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Environment variables downloaded to', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/.env.staging');
    }
}
