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

use Ymir\Cli\Command\Project\GetProjectInfoCommand;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetProjectInfoCommandTest extends TestCase
{
    public function testPerformGetsProjectInfo(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create([
            'id' => 1,
            'name' => 'my-project',
            'provider' => [
                'id' => 1,
                'name' => 'aws',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'support@ymirapp.com'],
                ],
            ],
            'region' => 'us-east-1',
        ]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new GetProjectInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetProjectInfoCommand::NAME, ['project' => '1']);

        $this->assertStringContainsString('my-project', $tester->getDisplay());
        $this->assertStringContainsString('aws', $tester->getDisplay());
        $this->assertStringContainsString('us-east-1', $tester->getDisplay());
        $this->assertStringContainsString('production', $tester->getDisplay());
    }

    public function testPerformGetsProjectInfoInteractively(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create([
            'id' => 1,
            'name' => 'my-project',
            'provider' => [
                'id' => 1,
                'name' => 'aws',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'support@ymirapp.com'],
                ],
            ],
            'region' => 'us-east-1',
        ]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new GetProjectInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetProjectInfoCommand::NAME, [], ['my-project']);

        $this->assertStringContainsString('my-project', $tester->getDisplay());
    }
}
