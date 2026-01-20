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

namespace Ymir\Cli\Tests\Integration\Command\Project;

use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Command\Project\RollbackProjectCommand;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RollbackProjectCommandTest extends TestCase
{
    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => [], 'staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymir.com']);
        $deployment1 = DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com']]);
        $deployment2 = DeploymentFactory::create(['id' => 2, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com']]);
        $rollback = DeploymentFactory::create(['id' => 3, 'status' => 'pending']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([
            EnvironmentFactory::create(['name' => 'production']),
            $environment,
        ]));
        $this->apiClient->shouldReceive('getDeployments')->with(\Mockery::type(Project::class), $environment)->andReturn(new ResourceCollection([$deployment2, $deployment1]));
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($deployment1);
        $this->apiClient->shouldReceive('getDeployment')->with(3)->andReturn($rollback);
        $this->apiClient->shouldReceive('createRollback')->once()->with(\Mockery::type(Project::class), $environment, $deployment1)->andReturn($rollback);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://staging.ymir.com');

        $this->bootApplication([
            new RollbackProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
            new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
        ]);

        $tester = $this->executeCommand(RollbackProjectCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Project rolled back successfully', $tester->getDisplay());
        $this->assertStringContainsString('https://staging.ymir.com', $tester->getDisplay());
    }

    public function testPerformRollsBackToPreviousDeployment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $deployment1 = DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com']]);
        $deployment2 = DeploymentFactory::create(['id' => 2, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com']]);
        $rollback = DeploymentFactory::create(['id' => 3, 'status' => 'pending']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('getDeployments')->with(\Mockery::type(Project::class), $environment)->andReturn(new ResourceCollection([$deployment2, $deployment1]));
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($deployment1);
        $this->apiClient->shouldReceive('getDeployment')->with(3)->andReturn($rollback);
        $this->apiClient->shouldReceive('createRollback')->once()->with(\Mockery::type(Project::class), $environment, $deployment1)->andReturn($rollback);

        $this->bootApplication([
            new RollbackProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
            new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
        ]);

        $tester = $this->executeCommand(RollbackProjectCommand::NAME, ['environment' => 'production']);

        $this->assertStringContainsString('Project rolled back successfully', $tester->getDisplay());
        $this->assertStringContainsString('https://vanity.ymir.com', $tester->getDisplay());
    }

    public function testPerformRollsBackToSelectedDeployment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);
        $deployment1 = DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com'], 'uuid' => 'uuid-1']);
        $deployment2 = DeploymentFactory::create(['id' => 2, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com'], 'uuid' => 'uuid-2']);
        $deployment3 = DeploymentFactory::create(['id' => 3, 'status' => 'finished', 'initiator' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@doe.com'], 'uuid' => 'uuid-3']);
        $rollback = DeploymentFactory::create(['id' => 4, 'status' => 'pending']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('getDeployments')->with(\Mockery::type(Project::class), $environment)->andReturn(new ResourceCollection([$deployment3, $deployment2, $deployment1]));
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($deployment1);
        $this->apiClient->shouldReceive('getDeployment')->with(4)->andReturn($rollback);
        $this->apiClient->shouldReceive('createRollback')->once()->with(\Mockery::type(Project::class), $environment, $deployment1)->andReturn($rollback);

        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->bootApplication([
            new RollbackProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
            new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ])),
        ]);

        $tester = $this->executeCommand(RollbackProjectCommand::NAME, ['environment' => 'production', '--select' => true], ['1']); // Selecting deployment 1

        $this->assertStringContainsString('Which deployment would you like to rollback to?', $tester->getDisplay());
        $this->assertStringContainsString('Project rolled back successfully', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfNoDeployments(): void
    {
        $this->expectException(ResourceStateException::class);
        $this->expectExceptionMessage('The "production" environment has never been deployed to');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('getDeployments')->with(\Mockery::type(Project::class), $environment)->andReturn(new ResourceCollection([]));

        $this->bootApplication([new RollbackProjectCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(RollbackProjectCommand::NAME, ['environment' => 'production']);
    }
}
