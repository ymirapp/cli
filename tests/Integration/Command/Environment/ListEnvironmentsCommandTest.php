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

use Ymir\Cli\Command\Environment\ListEnvironmentsCommand;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListEnvironmentsCommandTest extends TestCase
{
    public function testListEnvironments(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environments = new ResourceCollection([
            'staging' => EnvironmentFactory::create(['id' => 1, 'name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']),
            'production' => EnvironmentFactory::create(['id' => 2, 'name' => 'production', 'vanity_domain_name' => 'production.ymirapp.com']),
        ]);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection(['project' => $project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn($environments);

        $this->bootApplication([new ListEnvironmentsCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListEnvironmentsCommand::NAME, ['project' => 'project']);

        $this->assertStringContainsString('staging', $tester->getDisplay());
        $this->assertStringContainsString('production', $tester->getDisplay());
    }

    public function testListEnvironmentsInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environments = new ResourceCollection([
            'staging' => EnvironmentFactory::create(['id' => 1, 'name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']),
        ]);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection(['project' => $project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn($environments);

        $this->bootApplication([new ListEnvironmentsCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListEnvironmentsCommand::NAME, [], ['project']);

        $this->assertStringContainsString('staging', $tester->getDisplay());
    }
}
