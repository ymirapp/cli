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

use Ymir\Cli\Command\Environment\CreateEnvironmentCommand;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateEnvironmentCommandTest extends TestCase
{
    public function testCreateEnvironment(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('createEnvironment')->with($project, 'staging')->andReturn($environment);
        $this->projectTypeMock->shouldReceive('generateEnvironmentConfiguration')->with('staging')->andReturn(new EnvironmentConfiguration('staging', []));

        $this->bootApplication([new CreateEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateEnvironmentCommand::NAME, ['name' => 'staging']);

        $this->assertStringContainsString('Environment created', $tester->getDisplay());
        $this->assertTrue($this->projectConfiguration->hasEnvironment('staging'));
        $this->assertTrue($this->projectConfiguration->getEnvironmentConfiguration('staging')->isImageDeploymentType());
    }

    public function testCreateEnvironmentInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('createEnvironment')->with($project, 'staging')->andReturn($environment);
        $this->projectTypeMock->shouldReceive('generateEnvironmentConfiguration')->with('staging')->andReturn(new EnvironmentConfiguration('staging', []));

        $this->bootApplication([new CreateEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateEnvironmentCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Environment created', $tester->getDisplay());
        $this->assertTrue($this->projectConfiguration->hasEnvironment('staging'));
    }

    public function testCreateEnvironmentWithNoImageOption(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('createEnvironment')->with($project, 'staging')->andReturn($environment);
        $this->projectTypeMock->shouldReceive('generateEnvironmentConfiguration')->with('staging')->andReturn(new EnvironmentConfiguration('staging', []));

        $this->bootApplication([new CreateEnvironmentCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateEnvironmentCommand::NAME, ['name' => 'staging', '--no-image' => true]);

        $this->assertStringContainsString('Environment created', $tester->getDisplay());
        $this->assertFalse($this->projectConfiguration->getEnvironmentConfiguration('staging')->isImageDeploymentType());
    }
}
