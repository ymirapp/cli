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

namespace Ymir\Cli\Tests\Unit\Project\Deployment;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class StartAndMonitorDeploymentStepTest extends TestCase
{
    public function testPerformPrintsEachDeploymentStepOnlyOnceAcrossPolls(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(
                      DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'running']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'running']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]])
                  );

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldReceive('writeStep')->once()
               ->with('Creating infrastructure');

        $step = new StartAndMonitorDeploymentStep();

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformStartsAndMonitorsDeployment(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(
                      DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]])
                  );

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldReceive('writeStep')->once()
               ->with('Creating infrastructure');

        $step = new StartAndMonitorDeploymentStep();

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWhenDeploymentFails(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(
                      DeploymentFactory::create(['id' => 1, 'status' => 'failed', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'failed']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'failed', 'failed_message' => 'Terraform crashed'])
                  );

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldNotReceive('writeStep');

        $step = new StartAndMonitorDeploymentStep();

        $this->expectException(DeploymentFailedException::class);
        $this->expectExceptionMessage("Deployment failed with error message:\n\n\tTerraform crashed");

        $step->perform($context, $deployment, $environment);
    }
}
