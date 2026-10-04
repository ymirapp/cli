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
                'errors' => [0, 0],
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
                'errors' => [0, 0],
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
            'errors' => [],
            'cost_invocations' => 0,
            'cost_duration' => 0,
        ];

        $tester = $this->executeMetricsCommand(['website' => $series, 'console' => $series]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(2, preg_match_all('/\b0ms\b/', $tester->getDisplay()));
    }

    public function testGetEnvironmentMetricsWithFunctionErrors(): void
    {
        $series = [
            'invocations' => [2000, 3000],
            'duration' => [100000, 200000],
            'avg_duration' => [50, 67],
            'cost_invocations' => 1.25,
            'cost_duration' => 2.5,
        ];

        $tester = $this->executeMetricsCommand([
            'website' => $series + ['errors' => ['2026-10-02T12:34:00+00:00' => 1000, '2026-10-02T12:35:00+00:00' => 234]],
            'console' => $series + ['errors' => ['2026-10-02T12:34:00+00:00' => 3, '2026-10-02T12:35:00+00:00' => 9]],
        ]);

        [$website, $console] = explode('Console Lambda function', $tester->getDisplay());

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression('/Errors\s+1,234\s+-/', $website);
        $this->assertMatchesRegularExpression('/Errors\s+12\s+-/', $console);
        $this->assertMatchesRegularExpression('/Total\s+\$7\.50/', $console);
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

    public function testGetEnvironmentMetricsWithQueueFunctionEmptySeries(): void
    {
        $tester = $this->executeMetricsCommand([
            'queues' => [
                'default' => [
                    'invocations' => [],
                    'duration' => [],
                    'avg_duration' => [],
                    'errors' => [],
                    'cost_invocations' => 0,
                    'cost_duration' => 0,
                ],
            ],
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression('/Queue Lambda function \(default\)\s+Invocations\s+0\s+\$0\.00/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/Duration\s+0s\s+\$0\.00/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/Avg duration\s+0ms\s+-/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/Errors\s+0\s+-/', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWithQueueFunctions(): void
    {
        $tester = $this->executeMetricsCommand([
            'website' => [
                'invocations' => [10],
                'duration' => [1000],
                'avg_duration' => [100],
                'errors' => [0],
                'cost_invocations' => 1,
                'cost_duration' => 2,
            ],
            'queues' => [
                'default' => [
                    'invocations' => [1, 99],
                    'duration' => [100, 99000],
                    'avg_duration' => [100, 1000],
                    'errors' => [1, 2],
                    'cost_invocations' => 0.25,
                    'cost_duration' => 0.5,
                ],
                'emails' => [
                    'invocations' => [3, 1],
                    'duration' => [300, 5000],
                    'avg_duration' => [100, 5000],
                    'errors' => [0, 7],
                    'cost_invocations' => 0.1,
                    'cost_duration' => 0.2,
                ],
                'unavailable' => [],
            ],
        ]);

        [, $default] = explode('Queue Lambda function (default)', $tester->getDisplay());
        [$default, $emails] = explode('Queue Lambda function (emails)', $default);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression('/Invocations\s+100\s+\$0\.25/', $default);
        $this->assertMatchesRegularExpression('/Duration\s+99s\s+\$0\.50/', $default);
        $this->assertMatchesRegularExpression('/Avg duration\s+991ms\s+-/', $default);
        $this->assertMatchesRegularExpression('/Errors\s+3\s+-/', $default);
        $this->assertMatchesRegularExpression('/Invocations\s+4\s+\$0\.10/', $emails);
        $this->assertMatchesRegularExpression('/Duration\s+5s\s+\$0\.20/', $emails);
        $this->assertMatchesRegularExpression('/Avg duration\s+1,325ms\s+-/', $emails);
        $this->assertMatchesRegularExpression('/Errors\s+7\s+-/', $emails);
        $this->assertMatchesRegularExpression('/Total\s+\$4\.05/', $emails);
        $this->assertStringNotContainsString('unavailable', $tester->getDisplay());
    }

    public function testGetEnvironmentMetricsWithZeroAndEmptyFunctionErrors(): void
    {
        $series = [
            'invocations' => [10],
            'duration' => [100],
            'avg_duration' => [10],
            'cost_invocations' => 0,
            'cost_duration' => 0,
        ];

        $tester = $this->executeMetricsCommand([
            'website' => $series + ['errors' => []],
            'console' => $series + ['errors' => ['2026-10-02T12:34:00+00:00' => 0, '2026-10-02T12:35:00+00:00' => 0]],
        ]);

        [$website, $console] = explode('Console Lambda function', $tester->getDisplay());

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression('/Errors\s+0\s+-/', $website);
        $this->assertMatchesRegularExpression('/Errors\s+0\s+-/', $console);
    }

    public function testGetEnvironmentMetricsWithZeroInvocations(): void
    {
        $series = [
            'invocations' => [0, 0],
            'duration' => [0, 0],
            'avg_duration' => [0, 0],
            'errors' => [0, 0],
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
