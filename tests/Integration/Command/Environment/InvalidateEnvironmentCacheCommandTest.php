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

use Ymir\Cli\Command\Environment\InvalidateEnvironmentCacheCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class InvalidateEnvironmentCacheCommandTest extends TestCase
{
    public function testInvalidateEnvironmentCache(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('invalidateCache')->with($project, $environment, ['*'])->once();

        $this->bootApplication([new InvalidateEnvironmentCacheCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(InvalidateEnvironmentCacheCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Cache invalidation started', $tester->getDisplay());
    }

    public function testInvalidateEnvironmentCacheInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('invalidateCache')->with($project, $environment, ['*'])->once();

        $this->bootApplication([new InvalidateEnvironmentCacheCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(InvalidateEnvironmentCacheCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Cache invalidation started', $tester->getDisplay());
    }

    public function testInvalidateEnvironmentCacheWithPaths(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('invalidateCache')->with($project, $environment, ['/foo', '/bar'])->once();

        $this->bootApplication([new InvalidateEnvironmentCacheCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(InvalidateEnvironmentCacheCommand::NAME, ['environment' => 'staging', '--path' => ['/foo', '/bar']]);

        $this->assertStringContainsString('Cache invalidation started', $tester->getDisplay());
    }
}
