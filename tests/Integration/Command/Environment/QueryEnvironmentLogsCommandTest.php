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

use Ymir\Cli\Command\Environment\QueryEnvironmentLogsCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class QueryEnvironmentLogsCommandTest extends TestCase
{
    public function testQueryEnvironmentLogs(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentLogs')->with($project, $environment, 'website', \Mockery::any(), 'desc')->andReturn(collect([
            ['timestamp' => 1672531200000, 'message' => 'Log message 1'],
        ]));

        $this->bootApplication([new QueryEnvironmentLogsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(QueryEnvironmentLogsCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Log message 1', $tester->getDisplay());
    }

    public function testQueryEnvironmentLogsInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentLogs')->with($project, $environment, 'website', \Mockery::any(), 'desc')->andReturn(collect([
            ['timestamp' => 1672531200000, 'message' => 'Log message 1'],
        ]));

        $this->bootApplication([new QueryEnvironmentLogsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(QueryEnvironmentLogsCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Log message 1', $tester->getDisplay());
    }

    public function testQueryEnvironmentLogsWithFunctionAndOptions(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getEnvironmentLogs')->with($project, $environment, 'console', \Mockery::any(), 'desc')->andReturn(collect([
            ['timestamp' => 1672531260000, 'message' => 'Log message 2'],
            ['timestamp' => 1672531200000, 'message' => 'Log message 1'],
        ]));

        $this->bootApplication([new QueryEnvironmentLogsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(QueryEnvironmentLogsCommand::NAME, [
            'environment' => 'staging',
            'function' => 'console',
            '--lines' => 1,
            '--order' => 'desc',
            '--period' => '1d',
        ]);

        $this->assertStringContainsString('Log message 2', $tester->getDisplay());
        $this->assertStringNotContainsString('Log message 1', $tester->getDisplay());
    }
}
