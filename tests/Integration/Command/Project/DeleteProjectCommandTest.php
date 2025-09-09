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

namespace Ymir\Cli\Tests\Integration\Command\Project;

use Ymir\Cli\Command\Project\DeleteProjectCommand;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteProjectCommandTest extends TestCase
{
    public function testPerformDeletesConfigurationFileIfCurrentProject(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project');

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->apiClient->shouldReceive('deleteProject')->once()->with(\Mockery::type(Project::class), false);

        $this->bootApplication([new DeleteProjectCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $this->assertTrue($this->projectConfiguration->exists());

        $this->executeCommand(DeleteProjectCommand::NAME, ['project' => '1'], ['yes', 'no']);

        $this->assertFalse($this->projectConfiguration->exists());
    }

    public function testPerformDeletesProjectWithoutResources(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->apiClient->shouldReceive('deleteProject')->once()->with(\Mockery::on(function ($argument) use ($project) {
            return $argument instanceof Project && $argument->getId() === $project->getId();
        }), false);

        $this->bootApplication([new DeleteProjectCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteProjectCommand::NAME, ['project' => '1'], ['yes', 'no']);

        $this->assertStringContainsString('Project deleted', $tester->getDisplay());
    }

    public function testPerformDeletesProjectWithResources(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->apiClient->shouldReceive('deleteProject')->once()->with(\Mockery::on(function ($argument) use ($project) {
            return $argument instanceof Project && $argument->getId() === $project->getId();
        }), true);

        $this->bootApplication([new DeleteProjectCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteProjectCommand::NAME, ['project' => '1'], ['yes', 'yes']);

        $this->assertStringContainsString('Project deleted', $tester->getDisplay());
        $this->assertStringContainsString('process takes several minutes to complete', $tester->getDisplay());
    }
}
