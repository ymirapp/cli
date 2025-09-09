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
use Ymir\Cli\Command\Project\BuildProjectCommand;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Build\BuildContainerImageStep;
use Ymir\Cli\Project\Build\BuildStepInterface;
use Ymir\Cli\Project\Build\CompressBuildFilesStep;
use Ymir\Cli\Project\Build\CopyMediaDirectoryStep;
use Ymir\Cli\Project\Build\DebugBuildStep;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class BuildProjectCommandTest extends TestCase
{
    public function testPerformBuildsProject(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $step = \Mockery::mock(BuildStepInterface::class);
        $step->shouldReceive('getDescription')->andReturn('Doing something');
        $step->shouldReceive('perform')->once();

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform')->once();

        $buildStepLocator = new ServiceLocator([
            'step1' => function () use ($step) { return $step; },
            CompressBuildFilesStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn(['step1']);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(BuildProjectCommand::NAME, ['environment' => 'production']);

        $this->assertStringContainsString('Building my-project project for the production environment', $tester->getDisplay());
        $this->assertStringContainsString('Doing something', $tester->getDisplay());
        $this->assertStringContainsString('Compressing', $tester->getDisplay());
        $this->assertStringContainsString('Project built successfully', $tester->getDisplay());
    }

    public function testPerformBuildsProjectInDebugMode(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $debugStep = \Mockery::mock(BuildStepInterface::class);
        $debugStep->shouldReceive('getDescription')->andReturn('Debugging');
        $debugStep->shouldReceive('perform')->once();

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform')->once();

        $buildStepLocator = new ServiceLocator([
            DebugBuildStep::class => function () use ($debugStep) { return $debugStep; },
            CompressBuildFilesStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(BuildProjectCommand::NAME, ['environment' => 'production', '--debug' => true]);

        $this->assertStringContainsString('Debugging', $tester->getDisplay());
    }

    public function testPerformBuildsProjectWithImageDeploymentType(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => ['deployment' => ['type' => 'image']]]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $imageStep = \Mockery::mock(BuildStepInterface::class);
        $imageStep->shouldReceive('getDescription')->andReturn('Building image');
        $imageStep->shouldReceive('perform')->once();

        $buildStepLocator = new ServiceLocator([
            BuildContainerImageStep::class => function () use ($imageStep) { return $imageStep; },
        ]);

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(BuildProjectCommand::NAME, ['environment' => 'production']);

        $this->assertStringContainsString('Building image', $tester->getDisplay());
    }

    public function testPerformBuildsProjectWithMedia(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []], 'wordpress', \Ymir\Cli\Project\Type\WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));

        $mediaStep = \Mockery::mock(BuildStepInterface::class);
        $mediaStep->shouldReceive('getDescription')->andReturn('Copying media');
        $mediaStep->shouldReceive('perform')->once();

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform')->once();

        $buildStepLocator = new ServiceLocator([
            CopyMediaDirectoryStep::class => function () use ($mediaStep) { return $mediaStep; },
            CompressBuildFilesStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(BuildProjectCommand::NAME, ['environment' => 'production', '--with-media' => true]);

        $this->assertStringContainsString('Copying media', $tester->getDisplay());
    }

    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => [], 'staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([
            EnvironmentFactory::create(['name' => 'production']),
            $environment,
        ]));

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform')->once();

        $buildStepLocator = new ServiceLocator([
            CompressBuildFilesStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(BuildProjectCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Which my-project environment would you like to build?', $tester->getDisplay());
        $this->assertStringContainsString('Building my-project project for the staging environment', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfProjectDoesNotSupportMedia(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('This project type doesn\'t support media operations');

        $this->setupValidProject(1, 'project', ['production' => []], 'laravel');
        $environment = EnvironmentFactory::create(['name' => 'production']);
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));

        $buildStepLocator = new ServiceLocator([]);

        $this->bootApplication([new BuildProjectCommand($this->apiClient, $buildStepLocator, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $this->executeCommand(BuildProjectCommand::NAME, ['environment' => 'production', '--with-media' => true]);
    }
}
