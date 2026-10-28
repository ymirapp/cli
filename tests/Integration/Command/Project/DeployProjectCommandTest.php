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

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Command\Media\ImportMediaCommand;
use Ymir\Cli\Command\Project\BuildProjectCommand;
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\ValidateProjectCommand;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Build\BuildStepInterface;
use Ymir\Cli\Project\Build\BuildZipArchiveStep;
use Ymir\Cli\Project\Build\CopyMediaDirectoryStep;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Project\Deployment\WarnEndOfLifePhpVersionStep;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeployProjectCommandTest extends TestCase
{
    /**
     * @var Dockerfile|\Mockery\MockInterface
     */
    private $dockerfile;

    /**
     * @var FileUploader|\Mockery\MockInterface
     */
    private $uploader;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerfile = \Mockery::mock(Dockerfile::class);
        $this->uploader = \Mockery::mock(FileUploader::class);
    }

    public function testPerformDeploysProject(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $configuration = ['environments' => ['production' => ['php' => '7.4']]];
        $deployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'deployment', 'configuration' => $configuration]);
        $finishedDeployment = DeploymentFactory::create([
            'id' => 1,
            'status' => 'finished',
            'type' => 'deployment',
            'configuration' => $configuration,
            'steps' => [
                ['id' => 1, 'task' => 'UpdateLambdaFunctionJob', 'status' => 'finished'],
            ],
        ]);

        $assetsDir = $this->tempDir.'/.ymir/build/assets';
        $this->filesystem->mkdir($assetsDir);
        $this->filesystem->dumpFile($assetsDir.'/test.txt', 'content');

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('validateProjectConfiguration')->once()->andReturn(collect(['production' => ['warnings' => []]]));
        $this->apiClient->shouldReceive('createDeployment')->once()
                        ->with(\Mockery::type(Project::class), $environment, \Mockery::type('array'), \Mockery::type('string'))
                        ->andReturn($deployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($deployment);
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn($finishedDeployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform');

        $buildStepLocator = new ServiceLocator([
            BuildZipArchiveStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $contextFactory = $this->createExecutionContextFactoryWithEnvironment();

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $assetsDir, $contextFactory, $this->tempDir, [new WarnEndOfLifePhpVersionStep($this->dockerfile), new StartAndMonitorDeploymentStep()]),
            new ValidateProjectCommand($this->apiClient, $contextFactory, $this->dockerfile),
            new BuildProjectCommand($this->apiClient, $buildStepLocator, $contextFactory),
            new GetEnvironmentUrlCommand($this->apiClient, $contextFactory),
        ]);

        $tester = new CommandTester($this->application->find(DeployProjectCommand::NAME));
        $tester->execute(['environment' => 'production'], ['interactive' => false]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(1, substr_count($tester->getDisplay(), 'upstream end of life'));
        $this->assertStringContainsString('Warning: The "production" environment uses PHP 7.4 which reached its upstream end of life on 2022-11-28.', $tester->getDisplay());
        $this->assertStringContainsString('Deploying my-project to production', $tester->getDisplay());
        $this->assertStringContainsString('Updating lambda function', $tester->getDisplay());
        $this->assertStringContainsString('Project deployed successfully', $tester->getDisplay());
    }

    public function testPerformDeploysProjectInDebugMode(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $deployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'zip']);

        $assetsDir = $this->tempDir.'/.ymir/build/assets';
        $this->filesystem->mkdir($assetsDir);
        $this->filesystem->dumpFile($assetsDir.'/test.txt', 'content');

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('validateProjectConfiguration')->andReturn(collect(['production' => ['warnings' => []]]));
        $this->apiClient->shouldReceive('createDeployment')->once()->andReturn($deployment);
        $this->apiClient->shouldReceive('getDeployment')->andReturn($deployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $debugStep = \Mockery::mock(BuildStepInterface::class);
        $debugStep->shouldReceive('getDescription')->andReturn('Debugging');
        $debugStep->shouldReceive('perform')->once();

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform');

        $buildStepLocator = new ServiceLocator([
            \Ymir\Cli\Project\Build\DebugBuildStep::class => function () use ($debugStep) { return $debugStep; },
            BuildZipArchiveStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $contextFactory = $this->createExecutionContextFactoryWithEnvironment();

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $assetsDir, $contextFactory, $this->tempDir),
            new ValidateProjectCommand($this->apiClient, $contextFactory, $this->dockerfile),
            new BuildProjectCommand($this->apiClient, $buildStepLocator, $contextFactory),
            new GetEnvironmentUrlCommand($this->apiClient, $contextFactory),
        ]);

        $tester = $this->executeCommand(DeployProjectCommand::NAME, ['environment' => 'production', '--debug-build' => true]);

        $this->assertStringContainsString('Project deployed successfully', $tester->getDisplay());
        $this->assertStringContainsString('Debugging', $tester->getDisplay());
    }

    public function testPerformDeploysProjectWithMedia(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []], 'wordpress', \Ymir\Cli\Project\Type\WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $deployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'zip']);

        $assetsDir = $this->tempDir.'/.ymir/build/assets';
        $mediaDir = $this->tempDir.'/media';
        $this->filesystem->mkdir([$assetsDir, $mediaDir]);
        $this->filesystem->dumpFile($assetsDir.'/test.txt', 'content');
        $this->filesystem->dumpFile($mediaDir.'/image.jpg', 'image');

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('validateProjectConfiguration')->andReturn(collect(['production' => ['warnings' => []]]));
        $this->apiClient->shouldReceive('getSignedUploadRequests')->andReturn(collect(['image.jpg' => ['url' => 'https://s3.com']]));
        $this->apiClient->shouldReceive('createDeployment')->once()->andReturn($deployment);
        $this->apiClient->shouldReceive('getDeployment')->andReturn($deployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->uploader->shouldReceive('batch')->once();

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);
        $this->projectTypeMock->shouldReceive('getMediaDirectoryName')->andReturn('uploads');
        $this->projectTypeMock->shouldReceive('getMediaDirectoryPath')->andReturn($mediaDir);

        $mediaStep = \Mockery::mock(BuildStepInterface::class);
        $mediaStep->shouldReceive('getDescription')->andReturn('Copying media');
        $mediaStep->shouldReceive('perform');

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform');

        $buildStepLocator = new ServiceLocator([
            CopyMediaDirectoryStep::class => function () use ($mediaStep) { return $mediaStep; },
            BuildZipArchiveStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $contextFactory = $this->createExecutionContextFactoryWithEnvironment();

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $assetsDir, $contextFactory, $mediaDir),
            new ValidateProjectCommand($this->apiClient, $contextFactory, $this->dockerfile),
            new BuildProjectCommand($this->apiClient, $buildStepLocator, $contextFactory),
            new ImportMediaCommand($this->apiClient, $contextFactory, $this->uploader),
            new GetEnvironmentUrlCommand($this->apiClient, $contextFactory),
        ]);

        $tester = $this->executeCommand(DeployProjectCommand::NAME, ['environment' => 'production', '--with-media' => true]);

        $this->assertStringContainsString('Project deployed successfully', $tester->getDisplay());
    }

    public function testPerformPassesForceAssetsOptionToDeploymentSteps(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production', 'vanity_domain_name' => 'vanity.ymir.com']);
        $deployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'zip']);

        $assetsDir = $this->tempDir.'/.ymir/build/assets';
        $this->filesystem->mkdir($assetsDir);
        $this->filesystem->dumpFile($assetsDir.'/test.txt', 'content');

        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('validateProjectConfiguration')->andReturn(collect(['production' => ['warnings' => []]]));
        $this->apiClient->shouldReceive('createDeployment')->andReturn($deployment);
        $this->apiClient->shouldReceive('getDeployment')->andReturn($deployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://vanity.ymir.com');

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform');

        $buildStepLocator = new ServiceLocator([
            BuildZipArchiveStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $step = \Mockery::mock(\Ymir\Cli\Project\Deployment\DeploymentStepInterface::class);
        $step->shouldReceive('perform')->once()->withArgs(function ($context) {
            return true === $context->getInput()->getBooleanOption('force-assets');
        });

        $contextFactory = $this->createExecutionContextFactoryWithEnvironment();

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $assetsDir, $contextFactory, $this->tempDir, [$step]),
            new ValidateProjectCommand($this->apiClient, $contextFactory, $this->dockerfile),
            new BuildProjectCommand($this->apiClient, $buildStepLocator, $contextFactory),
            new GetEnvironmentUrlCommand($this->apiClient, $contextFactory),
        ]);

        $this->executeCommand(DeployProjectCommand::NAME, ['environment' => 'production', '--force-assets' => true]);
    }

    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->setupActiveTeam();
        $this->setupValidProject(1, 'my-project', ['production' => [], 'staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymir.com']);
        $deployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'zip']);

        $assetsDir = $this->tempDir.'/.ymir/build/assets';
        $this->filesystem->mkdir($assetsDir);
        $this->filesystem->dumpFile($assetsDir.'/test.txt', 'content');

        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([
            EnvironmentFactory::create(['name' => 'production']),
            $environment,
        ]));
        $this->apiClient->shouldReceive('validateProjectConfiguration')->andReturn(collect(['staging' => ['warnings' => []]]));
        $this->apiClient->shouldReceive('createDeployment')->once()->andReturn($deployment);
        $this->apiClient->shouldReceive('getDeployment')->andReturn($deployment);
        $this->apiClient->shouldReceive('getEnvironmentUrl')->andReturn('https://staging.ymir.com');

        $this->projectTypeMock->shouldReceive('getBuildSteps')->andReturn([]);

        $compressStep = \Mockery::mock(BuildStepInterface::class);
        $compressStep->shouldReceive('getDescription')->andReturn('Compressing');
        $compressStep->shouldReceive('perform');

        $buildStepLocator = new ServiceLocator([
            BuildZipArchiveStep::class => function () use ($compressStep) { return $compressStep; },
        ]);

        $contextFactory = $this->createExecutionContextFactoryWithEnvironment();

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $assetsDir, $contextFactory, $this->tempDir),
            new ValidateProjectCommand($this->apiClient, $contextFactory, $this->dockerfile),
            new BuildProjectCommand($this->apiClient, $buildStepLocator, $contextFactory),
            new GetEnvironmentUrlCommand($this->apiClient, $contextFactory),
        ]);

        $tester = $this->executeCommand(DeployProjectCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('Project deployed successfully', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfProjectDoesNotSupportMedia(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('This project type doesn\'t support media operations');

        $this->setupValidProject(1, 'project', ['production' => []], 'laravel');
        $environment = EnvironmentFactory::create(['name' => 'production']);
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));

        $this->bootApplication([
            new DeployProjectCommand($this->apiClient, $this->tempDir, $this->createExecutionContextFactoryWithEnvironment(), $this->tempDir),
        ]);

        $this->executeCommand(DeployProjectCommand::NAME, ['environment' => 'production', '--with-media' => true]);
    }

    /**
     * Create an execution context factory with the environment resource definition.
     */
    private function createExecutionContextFactoryWithEnvironment()
    {
        return $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]);
    }
}
