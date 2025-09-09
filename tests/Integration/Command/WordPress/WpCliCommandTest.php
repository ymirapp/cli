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

namespace Ymir\Cli\Tests\Integration\Command\WordPress;

use Ymir\Cli\Command\WordPress\WpCliCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class WpCliCommandTest extends TestCase
{
    public function testPerformExecutesWpCliCommand(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp plugin list'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'plugin list output',
            ],
        ]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(WpCliCommand::NAME, ['wp-command' => ['plugin', 'list'], '--environment' => 'production']);

        $this->assertStringContainsString('Running "wp plugin list" on "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('plugin list output', $tester->getDisplay());
    }

    public function testPerformExecutesWpCliCommandAsynchronously(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp cache flush'])->andReturn(collect(['id' => 123]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(WpCliCommand::NAME, ['wp-command' => ['cache', 'flush'], '--environment' => 'production', '--async' => true]);

        $this->assertStringContainsString('Running "wp cache flush" asynchronously on "production" environment', $tester->getDisplay());
    }

    public function testPerformExecutesWpCliCommandInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp post list'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'post list output',
            ],
        ]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(WpCliCommand::NAME, [], ['production', 'post list']);

        $this->assertStringContainsString('Running "wp post list" on "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('post list output', $tester->getDisplay());
    }

    public function testPerformRemovesWpPrefixFromCommand(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp plugin list'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'plugin list output',
            ],
        ]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(WpCliCommand::NAME, ['wp-command' => ['wp', 'plugin', 'list'], '--environment' => 'production']);

        $this->assertStringContainsString('Running "wp plugin list" on "production" environment', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfCommandIsDbImport(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Please use the "ymir database:import" command instead of the "wp db import" command');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', [], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(WpCliCommand::NAME, ['wp-command' => ['db', 'import'], '--environment' => 'production']);
    }

    public function testPerformThrowsExceptionIfCommandIsShell(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "wp shell" command isn\'t available remotely');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', [], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(WpCliCommand::NAME, ['wp-command' => ['shell'], '--environment' => 'production']);
    }

    public function testPerformThrowsExceptionIfProjectIsNotWordPress(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this command with WordPress, Bedrock or Radicle projects');

        $this->setupValidProject(1, 'project', [], 'laravel');

        $this->bootApplication([new WpCliCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $this->executeCommand(WpCliCommand::NAME);
    }
}
