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

use Ymir\Cli\Command\WordPress\ChangeDomainCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ChangeDomainCommandTest extends TestCase
{
    public function testPerformChangesDomainInteractivelyWithVanityDomain(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp search-replace vanity.ymir.com new.com --all-tables'])->once()->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'search-replace output',
            ],
        ]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp cache flush'])->once()->andReturn(collect(['id' => 124]));
        $this->apiClient->shouldReceive('getInvocation')->with(124)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'cache flush output',
            ],
        ]));

        $this->bootApplication([new ChangeDomainCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeDomainCommand::NAME, [], [
            'production', // Which environment
            'yes',        // Use vanity domain as current?
            'new.com',    // New domain
            'yes',        // Are you sure?
            'no',         // Add new domain to configuration?
        ]);

        $this->assertStringContainsString('Changing "production" environment domain from "vanity.ymir.com" to "new.com"', $tester->getDisplay());
    }

    public function testPerformChangesDomainWithArguments(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => ['domain' => ['new.com']]], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp search-replace current.com new.com --all-tables'])->once()->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'search-replace output',
            ],
        ]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => 'bin/wp cache flush'])->once()->andReturn(collect(['id' => 124]));
        $this->apiClient->shouldReceive('getInvocation')->with(124)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'cache flush output',
            ],
        ]));

        $this->bootApplication([new ChangeDomainCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(ChangeDomainCommand::NAME, [
            'environment' => 'production',
            'domain' => 'current.com',
        ], ['yes']); // Confirmation question

        $this->assertStringContainsString('Changing "production" environment domain from "current.com" to "new.com"', $tester->getDisplay());
        $this->assertStringContainsString('Environment domain changed', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfDomainsAreIdentical(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Both current and new environment domain are identical');

        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => ['domain' => ['example.com']]], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([new ChangeDomainCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(ChangeDomainCommand::NAME, ['environment' => 'production', 'domain' => 'example.com']);
    }

    public function testPerformThrowsExceptionIfProjectIsNotWordPress(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this command with WordPress, Bedrock or Radicle projects');

        $this->setupValidProject(1, 'project', [], 'laravel');

        $this->bootApplication([new ChangeDomainCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $this->executeCommand(ChangeDomainCommand::NAME);
    }
}
