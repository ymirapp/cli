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

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Yaml\Yaml;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Initialization\InitializationStepInterface;
use Ymir\Cli\Project\Type\InstallableProjectTypeInterface;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class InitializeProjectCommandTest extends TestCase
{
    public function testPerformAutoDetectsProjectType(): void
    {
        $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createProject')->once()->andReturn($project);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('Detected type');
        $projectType->shouldReceive('getSlug')->andReturn('detected');
        $projectType->shouldReceive('getInitializationSteps')->andReturn([]);
        $projectType->shouldReceive('generateEnvironmentConfiguration')->andReturnUsing(function ($environment) {
            return new EnvironmentConfiguration($environment, []);
        });
        $projectType->shouldReceive('matchesProject')->andReturn(true);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), new ServiceLocator([]), [$projectType]),
        ]);

        $tester = $this->executeCommand(InitializeProjectCommand::NAME, [], [
            'my-project', // Project name
            '1', // Select AWS provider
            'us-east-1', // Select region
        ]);

        $this->assertStringContainsString('Initialized Detected type project "my-project"', $tester->getDisplay());
    }

    public function testPerformDoesNotOverwriteProjectIfCancelled(): void
    {
        $this->setupActiveTeam();

        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('Type');
        $projectType->shouldReceive('getSlug')->andReturn('type');
        $projectType->shouldReceive('matchesProject')->andReturn(false);

        // Setup initial project config
        $initialProject = ProjectFactory::create(['id' => 1, 'name' => 'old-project']);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($initialProject);
        $this->projectConfiguration->createNew($initialProject, collect(), $projectType);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), new ServiceLocator([]), [$projectType]),
        ]);

        $tester = $this->executeCommand(InitializeProjectCommand::NAME, [], [
            'no', // Overwrite?
        ]);

        $this->assertStringContainsString('name: old-project', file_get_contents($this->tempDir.'/ymir.yml'));
    }

    public function testPerformHandlesInitializationSteps(): void
    {
        $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createProject')->once()->andReturn($project);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('Type');
        $projectType->shouldReceive('getSlug')->andReturn('type');
        $projectType->shouldReceive('getInitializationSteps')->andReturn(['step1']);
        $projectType->shouldReceive('generateEnvironmentConfiguration')->andReturnUsing(function ($environment) {
            return new EnvironmentConfiguration($environment, []);
        });
        $projectType->shouldReceive('matchesProject')->andReturn(false);

        $configChange = \Mockery::mock(ConfigurationChangeInterface::class);
        $configChange->shouldReceive('apply')->andReturnUsing(function ($config) {
            return new EnvironmentConfiguration($config->getName(), array_merge($config->toArray(), ['modified' => true]));
        });

        $step = \Mockery::mock(InitializationStepInterface::class);
        $step->shouldReceive('perform')->once()->andReturn($configChange);

        $initializationStepLocator = new ServiceLocator([
            'step1' => function () use ($step) { return $step; },
        ]);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), $initializationStepLocator, [$projectType]),
        ]);

        $this->executeCommand(InitializeProjectCommand::NAME, [], [
            '0', // Select project type
            'my-project', // Project name
            '1', // Select AWS provider
            'us-east-1', // Select region
        ]);

        $config = Yaml::parse(file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertTrue($config['environments']['production']['modified']);
    }

    public function testPerformInitializesNewProject(): void
    {
        $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createProject')->once()->with($provider, 'my-project', 'us-east-1', Project::DEFAULT_ENVIRONMENTS)->andReturn($project);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('My project type');
        $projectType->shouldReceive('getSlug')->andReturn('my-project-type');
        $projectType->shouldReceive('getInitializationSteps')->andReturn([]);
        $projectType->shouldReceive('generateEnvironmentConfiguration')->andReturnUsing(function ($environment) {
            return new EnvironmentConfiguration($environment, []);
        });
        $projectType->shouldReceive('matchesProject')->andReturn(false);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), new ServiceLocator([]), [$projectType]),
        ]);

        $tester = $this->executeCommand(InitializeProjectCommand::NAME, [], [
            '0', // Select project type
            'my-project', // Project name
            '1', // Select AWS provider
            'us-east-1', // Select region
        ]);

        $this->assertStringContainsString('Initialized My project type project "my-project"', $tester->getDisplay());
        $this->assertFileExists($this->tempDir.'/ymir.yml');
        $this->assertStringContainsString('name: my-project', file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertStringContainsString('type: my-project-type', file_get_contents($this->tempDir.'/ymir.yml'));
    }

    public function testPerformInitializesNewProjectWithInstallation(): void
    {
        $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $project = ProjectFactory::create(['id' => 1, 'name' => 'my-project']);

        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createProject')->once()->andReturn($project);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $projectType = \Mockery::mock(ProjectTypeInterface::class, InstallableProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('Installable type');
        $projectType->shouldReceive('getSlug')->andReturn('installable');
        $projectType->shouldReceive('getInitializationSteps')->andReturn([]);
        $projectType->shouldReceive('generateEnvironmentConfiguration')->andReturnUsing(function ($environment) {
            return new EnvironmentConfiguration($environment, []);
        });
        $projectType->shouldReceive('isEligibleForInstallation')->with($this->tempDir)->andReturn(true);
        $projectType->shouldReceive('getInstallationMessage')->andReturn('Installing something');
        $projectType->shouldReceive('installProject')->once()->with($this->tempDir);
        $projectType->shouldReceive('matchesProject')->andReturn(false);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), new ServiceLocator([]), [$projectType]),
        ]);

        $tester = $this->executeCommand(InitializeProjectCommand::NAME, [], [
            '0', // Select project type
            'yes', // Confirm installation
            'my-project', // Project name
            '1', // Select AWS provider
            'us-east-1', // Select region
        ]);

        $this->assertStringContainsString('Installing something', $tester->getDisplay());
        $this->assertStringContainsString('Initialized Installable type project "my-project"', $tester->getDisplay());
    }

    public function testPerformOverwritesExistingProject(): void
    {
        $this->setupActiveTeam();

        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectType->shouldReceive('getName')->andReturn('Type');
        $projectType->shouldReceive('getSlug')->andReturn('type');
        $projectType->shouldReceive('getInitializationSteps')->andReturn([]);
        $projectType->shouldReceive('generateEnvironmentConfiguration')->andReturnUsing(function ($environment) {
            return new EnvironmentConfiguration($environment, []);
        });
        $projectType->shouldReceive('matchesProject')->andReturn(false);

        // Setup initial project config
        $initialProject = ProjectFactory::create(['id' => 1, 'name' => 'old-project']);
        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($initialProject);
        $this->projectConfiguration->createNew($initialProject, collect(), $projectType);

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $newProject = ProjectFactory::create(['id' => 2, 'name' => 'new-project']);

        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createProject')->once()->andReturn($newProject);
        $this->apiClient->shouldReceive('getProject')->with(2)->andReturn($newProject);

        $this->bootApplication([
            new InitializeProjectCommand($this->apiClient, $this->createExecutionContextFactory([
                CloudProvider::class => function () { return new CloudProviderDefinition(); },
                Project::class => function () { return new ProjectDefinition(); },
            ]), new ServiceLocator([]), [$projectType]),
        ]);

        $tester = $this->executeCommand(InitializeProjectCommand::NAME, [], [
            'yes', // Overwrite?
            '0', // Select project type
            'new-project', // Project name
            '1', // Select AWS provider
            'us-east-1', // Select region
        ]);

        $this->assertStringContainsString('Initialized Type project "new-project"', $tester->getDisplay());
        $this->assertStringContainsString('name: new-project', file_get_contents($this->tempDir.'/ymir.yml'));
    }
}
