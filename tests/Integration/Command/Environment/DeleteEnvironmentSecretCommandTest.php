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

use Ymir\Cli\Command\Environment\DeleteEnvironmentSecretCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteEnvironmentSecretCommandTest extends TestCase
{
    public function testDeleteEnvironmentSecret(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);
        $secret = SecretFactory::create(['id' => 1, 'name' => 'FOO']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getSecrets')->with($project, $environment)->andReturn(new ResourceCollection(['FOO' => $secret]));
        $this->apiClient->shouldReceive('deleteSecret')->with($secret)->once();

        $this->bootApplication([new DeleteEnvironmentSecretCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentSecretCommand::NAME, ['environment' => 'staging', 'secret' => 'FOO'], ['yes']);

        $this->assertStringContainsString('Secret deleted', $tester->getDisplay());
    }

    public function testDeleteEnvironmentSecretCancellation(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);
        $secret = SecretFactory::create(['id' => 1, 'name' => 'FOO']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getSecrets')->with($project, $environment)->andReturn(new ResourceCollection(['FOO' => $secret]));
        $this->apiClient->shouldNotReceive('deleteSecret');

        $this->bootApplication([new DeleteEnvironmentSecretCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentSecretCommand::NAME, ['environment' => 'staging', 'secret' => 'FOO'], ['no']);

        $this->assertStringNotContainsString('Secret deleted', $tester->getDisplay());
    }

    public function testDeleteEnvironmentSecretInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);
        $secret = SecretFactory::create(['id' => 1, 'name' => 'FOO']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));
        $this->apiClient->shouldReceive('getSecrets')->with($project, $environment)->andReturn(new ResourceCollection(['FOO' => $secret]));
        $this->apiClient->shouldReceive('deleteSecret')->with($secret)->once();

        $this->bootApplication([new DeleteEnvironmentSecretCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(DeleteEnvironmentSecretCommand::NAME, [], ['staging', 'FOO', 'yes']);

        $this->assertStringContainsString('Secret deleted', $tester->getDisplay());
    }
}
