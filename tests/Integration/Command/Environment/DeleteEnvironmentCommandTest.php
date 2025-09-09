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

use Ymir\Cli\Command\Environment\DeleteEnvironmentCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteEnvironmentCommandTest extends TestCase
{
    public function testDeleteEnvironment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('deleteEnvironment')->with($project, $environment, false)->once();

        $this->bootApplication([new DeleteEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentCommand::NAME, ['environment' => 'staging'], ['yes', 'no']);

        $this->assertStringContainsString('Environment deleted', $tester->getDisplay());
        $this->assertFalse($this->projectConfiguration->hasEnvironment('staging'));
    }

    public function testDeleteEnvironmentCancellation(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldNotReceive('deleteEnvironment');

        $this->bootApplication([new DeleteEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentCommand::NAME, ['environment' => 'staging'], ['no']);

        $this->assertStringNotContainsString('Environment deleted', $tester->getDisplay());
        $this->assertTrue($this->projectConfiguration->hasEnvironment('staging'));
    }

    public function testDeleteEnvironmentInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('deleteEnvironment')->with($project, $environment, false)->once();

        $this->bootApplication([new DeleteEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentCommand::NAME, [], ['staging', 'yes', 'no']);

        $this->assertStringContainsString('Environment deleted', $tester->getDisplay());
        $this->assertFalse($this->projectConfiguration->hasEnvironment('staging'));
    }

    public function testDeleteEnvironmentWithDeleteResourcesOption(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('deleteEnvironment')->with($project, $environment, true)->once();

        $this->bootApplication([new DeleteEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentCommand::NAME, ['environment' => 'staging', '--delete-resources' => true], ['yes']);

        $this->assertStringContainsString('Environment deleted', $tester->getDisplay());
        $this->assertStringContainsString('process takes several minutes to complete', $tester->getDisplay());
    }
}
