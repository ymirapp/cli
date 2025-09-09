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

use Ymir\Cli\Command\Environment\GetEnvironmentInfoCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetEnvironmentInfoCommandTest extends TestCase
{
    public function testGetEnvironmentInfo(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection(['project' => $project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));

        $this->bootApplication([new GetEnvironmentInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentInfoCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('staging', $tester->getDisplay());
        $this->assertStringContainsString('staging.ymirapp.com', $tester->getDisplay());
    }

    public function testGetEnvironmentInfoForAllEnvironments(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => [], 'production' => []]);
        $environments = new ResourceCollection([
            'staging' => EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']),
            'production' => EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'production.ymirapp.com']),
        ]);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection(['project' => $project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn($environments);

        $this->bootApplication([new GetEnvironmentInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentInfoCommand::NAME);

        $this->assertStringContainsString('Listing information on all project environments', $tester->getDisplay());
        $this->assertStringContainsString('staging', $tester->getDisplay());
        $this->assertStringContainsString('production', $tester->getDisplay());
    }

    public function testGetEnvironmentInfoInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']);

        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection(['project' => $project]));
        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));

        $this->bootApplication([new GetEnvironmentInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
            Project::class => function () { return new ProjectDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentInfoCommand::NAME, [], ['project']);

        $this->assertStringContainsString('staging', $tester->getDisplay());
    }
}
