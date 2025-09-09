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

use Ymir\Cli\Command\Environment\ListEnvironmentSecretsCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListEnvironmentSecretsCommandTest extends TestCase
{
    public function testListEnvironmentSecrets(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);
        $secrets = new ResourceCollection([
            'FOO' => SecretFactory::create(['id' => 1, 'name' => 'FOO', 'updated_at' => '2023-01-01 00:00:00']),
            'BAR' => SecretFactory::create(['id' => 2, 'name' => 'BAR', 'updated_at' => '2023-01-01 00:00:00']),
        ]);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getSecrets')->with($project, $environment)->andReturn($secrets);

        $this->bootApplication([new ListEnvironmentSecretsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListEnvironmentSecretsCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('FOO', $tester->getDisplay());
        $this->assertStringContainsString('BAR', $tester->getDisplay());
    }

    public function testListEnvironmentSecretsInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getSecrets')->with($project, $environment)->andReturn(new ResourceCollection([]));

        $this->bootApplication([new ListEnvironmentSecretsCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ListEnvironmentSecretsCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Id', $tester->getDisplay());
    }
}
