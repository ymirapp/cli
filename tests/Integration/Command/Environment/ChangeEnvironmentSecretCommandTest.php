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

use Ymir\Cli\Command\Environment\ChangeEnvironmentSecretCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ChangeEnvironmentSecretCommandTest extends TestCase
{
    public function testChangeEnvironmentSecret(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeSecret')->with($project, $environment, 'FOO', 'BAR')->once();

        $this->bootApplication([new ChangeEnvironmentSecretCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeEnvironmentSecretCommand::NAME, ['environment' => 'staging', 'name' => 'FOO', 'value' => 'BAR']);

        $this->assertStringContainsString('Secret changed', $tester->getDisplay());
    }

    public function testChangeEnvironmentSecretInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('changeSecret')->with($project, $environment, 'FOO', 'BAR')->once();

        $this->bootApplication([new ChangeEnvironmentSecretCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeEnvironmentSecretCommand::NAME, [], ['staging', 'FOO', 'BAR']);

        $this->assertStringContainsString('Secret changed', $tester->getDisplay());
    }
}
