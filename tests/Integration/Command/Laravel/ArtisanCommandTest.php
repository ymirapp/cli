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

namespace Ymir\Cli\Tests\Integration\Command\Laravel;

use Ymir\Cli\Command\Laravel\ArtisanCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ArtisanCommandTest extends TestCase
{
    public function testPerformDoesNotAddNoAnsiOptionWhenAnsiOptionIsPresent(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'artisan migrate --ansi'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'migrate output',
            ],
        ]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['migrate', '--ansi'], '--environment' => 'production']);

        $this->assertStringContainsString('Running "php artisan migrate --ansi" on "production" environment', $tester->getDisplay());
    }

    public function testPerformExecutesArtisanCommand(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'artisan migrate:status --no-ansi'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'artisan output',
            ],
        ]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['migrate:status'], '--environment' => 'production']);

        $this->assertStringContainsString('Running "php artisan migrate:status" on "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('artisan output', $tester->getDisplay());
    }

    public function testPerformExecutesArtisanCommandAsynchronously(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'artisan cache:clear --no-ansi'])->andReturn(collect(['id' => 123]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['cache:clear'], '--environment' => 'production', '--async' => true]);

        $this->assertStringContainsString('Running "php artisan cache:clear" asynchronously on "production" environment', $tester->getDisplay());
    }

    public function testPerformExecutesArtisanCommandInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'artisan route:list --no-ansi'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'route list output',
            ],
        ]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ArtisanCommand::NAME, [], ['production', 'route:list']);

        $this->assertStringContainsString('Running "php artisan route:list" on "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('route list output', $tester->getDisplay());
    }

    public function testPerformRemovesPhpArtisanPrefixFromCommand(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'artisan migrate --no-ansi'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'migrate output',
            ],
        ]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['php', 'artisan', 'migrate'], '--environment' => 'production']);

        $this->assertStringContainsString('Running "php artisan migrate" on "production" environment', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfCommandIsTinker(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "artisan tinker" command isn\'t available remotely');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', [], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['tinker'], '--environment' => 'production']);
    }

    public function testPerformThrowsExceptionIfCommandIsTinkerWithArguments(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "artisan tinker --execute="dump(1);"" command isn\'t available remotely');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', [], 'laravel', LaravelProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(ArtisanCommand::NAME, ['artisan-command' => ['tinker', '--execute="dump(1);"'], '--environment' => 'production']);
    }

    public function testPerformThrowsExceptionIfProjectIsNotLaravel(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this command with Laravel projects');

        $this->setupValidProject(1, 'project', [], 'wordpress');

        $this->bootApplication([new ArtisanCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $this->executeCommand(ArtisanCommand::NAME);
    }
}
