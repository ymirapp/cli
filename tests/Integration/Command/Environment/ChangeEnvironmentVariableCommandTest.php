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

use Ymir\Cli\Command\Environment\ChangeEnvironmentVariableCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ChangeEnvironmentVariableCommandTest extends TestCase
{
    public function testChangeEnvironmentVariable(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->with($project, $environment, ['FOO' => 'BAR'])->once();

        $this->bootApplication([new ChangeEnvironmentVariableCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeEnvironmentVariableCommand::NAME, ['environment' => 'staging', 'name' => 'FOO', 'value' => 'BAR']);

        $this->assertStringContainsString('Environment variable changed', $tester->getDisplay());
    }

    public function testChangeEnvironmentVariableInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->with($project, $environment, ['FOO' => 'BAR'])->once();

        $this->bootApplication([new ChangeEnvironmentVariableCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeEnvironmentVariableCommand::NAME, [], ['staging', 'FOO', 'BAR']);

        $this->assertStringContainsString('Environment variable changed', $tester->getDisplay());
    }
}
