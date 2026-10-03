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

use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Environment\GetEnvironmentMetricsCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetEnvironmentMetricsCommandTest extends TestCase
{
    public function testGetEnvironmentMetrics(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentMetrics')->with($project, $environment, '1d')->andReturn(collect([
            'cdn' => [
                'bandwidth' => [1000000000],
                'requests' => [1000],
                'cost_bandwidth' => 0.1,
                'cost_requests' => 0.01,
            ],
        ]));

        $this->bootApplication([new GetEnvironmentMetricsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentMetricsCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Content Delivery Network', $tester->getDisplay());
        $this->assertStringContainsString('1.00GB', $tester->getDisplay());
        $this->assertStringContainsString('$0.10', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentMetrics')->with($project, $environment, '1d')->andReturn(collect([]));

        $this->bootApplication([new GetEnvironmentMetricsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentMetricsCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Environment: staging', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWeightsConsoleAverageDurationByInvocations(): void
    {
        $tester = $this->executeMetricsCommand([
            'console' => [
                'invocations' => [3, 1],
                'duration' => [300, 5000],
                'avg_duration' => [100, 5000],
                'cost_invocations' => 0,
                'cost_duration' => 0,
            ],
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Console Lambda function', $tester->getDisplay());
        $this->assertStringContainsString('1,325ms', $tester->getDisplay());
        $this->assertStringNotContainsString('2,550ms', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWeightsWebsiteAverageDurationByInvocations(): void
    {
        $tester = $this->executeMetricsCommand([
            'website' => [
                'invocations' => [1, 99],
                'duration' => [100, 99000],
                'avg_duration' => [100, 1000],
                'cost_invocations' => 0,
                'cost_duration' => 0,
            ],
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Website Lambda function', $tester->getDisplay());
        $this->assertStringContainsString('991ms', $tester->getDisplay());
        $this->assertStringNotContainsString('550ms', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWithEmptyFunctionSeries(): void
    {
        $series = [
            'invocations' => [],
            'duration' => [],
            'avg_duration' => [],
            'cost_invocations' => 0,
            'cost_duration' => 0,
        ];

        $tester = $this->executeMetricsCommand(['website' => $series, 'console' => $series]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(2, preg_match_all('/\b0ms\b/', $tester->getDisplay()));
    }

    public function testGetEnvironmentMetricsWithPeriodOption(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentMetrics')->with($project, $environment, '1mo')->andReturn(collect([]));

        $this->bootApplication([new GetEnvironmentMetricsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentMetricsCommand::NAME, ['environment' => 'staging', '--period' => '1mo']);

        $this->assertStringContainsString('Environment: staging', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWithZeroInvocations(): void
    {
        $series = [
            'invocations' => [0, 0],
            'duration' => [0, 0],
            'avg_duration' => [0, 0],
            'cost_invocations' => 0,
            'cost_duration' => 0,
        ];

        $tester = $this->executeMetricsCommand(['website' => $series, 'console' => $series]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(2, preg_match_all('/\b0ms\b/', $tester->getDisplay()));
    }

    private function executeMetricsCommand(array $metrics): CommandTester
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentMetrics')->once()->with($project, $environment, '1d')->andReturn(collect($metrics));

        $this->bootApplication([new GetEnvironmentMetricsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        return $this->executeCommand(GetEnvironmentMetricsCommand::NAME, ['environment' => 'staging']);
    }
}
