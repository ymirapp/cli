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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\TestCase;

class StartAndMonitorDeploymentStepTest extends TestCase
{
    public function testPerformStartsAndMonitorsDeployment(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'zip', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getApiClient')->times(2)
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]]));

        $output->shouldReceive('info')->once()
               ->with('Zip starting');

        $output->shouldReceive('writeStep')->once()
               ->with('Creating infrastructure');

        $step = new StartAndMonitorDeploymentStep();

        $step->perform($context, $deployment, $environment);
    }
}
