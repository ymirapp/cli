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

use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RedeployProjectCommandTest extends TestCase
{
    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => [], 'staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymir.com']);
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);
        $finishedRedeployment = DeploymentFactory::create([
            'id' => 1,
            'status' => 'finished',
            'type' => 'redeployment',
            'steps' => [
                ['id' => 1, 'task' => 'UpdateLambdaFunctionJob', 'status' => 'finished'],
            ],
        ]);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([
            EnvironmentFactory::create(['name' => 'production']),
            $environment,
        ]));
        $this->apiClient->shouldReceive('createRedeployment')->once()->with(\Mockery::type(Project::class), $environment)->andReturn($redeployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($redeployment);
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($finishedRedeployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://staging.ymir.com');

        $this->bootApplication([
            new RedeployProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ]), [new StartAndMonitorDeploymentStep()]),
            new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
        ]);

        $tester = $this->executeCommand(RedeployProjectCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Project redeployed successfully to "staging" environment', $tester->getDisplay());
        $this->assertStringContainsString('https://staging.ymir.com', $tester->getDisplay());
    }

    public function testPerformRedeploysProject(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);
        $finishedRedeployment = DeploymentFactory::create([
            'id' => 1,
            'status' => 'finished',
            'type' => 'redeployment',
            'steps' => [
                ['id' => 1, 'task' => 'UpdateLambdaFunctionJob', 'status' => 'finished'],
            ],
        ]);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createRedeployment')->once()->with(\Mockery::type(Project::class), $environment)->andReturn($redeployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($redeployment);
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($finishedRedeployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->bootApplication([
            new RedeployProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ]), [new StartAndMonitorDeploymentStep()]),
            new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
        ]);

        $tester = $this->executeCommand(RedeployProjectCommand::NAME, ['environment' => 'production']);

        $this->assertStringContainsString('Redeployment starting', $tester->getDisplay());
        $this->assertStringContainsString('Updating lambda function', $tester->getDisplay());
        $this->assertStringContainsString('Project redeployed successfully to "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('https://vanity.ymir.com', $tester->getDisplay());
    }
}
