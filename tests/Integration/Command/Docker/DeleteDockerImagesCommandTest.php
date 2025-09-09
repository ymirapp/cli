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

namespace Ymir\Cli\Tests\Integration\Command\Docker;

use Ymir\Cli\Command\Docker\DeleteDockerImagesCommand;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDockerImagesCommandTest extends TestCase
{
    /**
     * @var DockerExecutable|\Mockery\MockInterface
     */
    private $dockerExecutable;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerExecutable = \Mockery::mock(DockerExecutable::class);
    }

    public function testDeclineDeleteAllImages(): void
    {
        $this->setupActiveTeam();

        $this->dockerExecutable->shouldNotReceive('removeImagesMatchingPattern');

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, ['--all' => true], ['no']);

        $this->assertStringNotContainsString('All local Ymir deployment docker images deleted successfully', $tester->getDisplay());
    }

    public function testDeclineDeleteProjectImages(): void
    {
        $team = $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'project-with-images', 'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images']);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project]));
        $this->dockerExecutable->shouldNotReceive('removeImagesMatchingPattern');

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, [], ['1', 'no']);

        $this->assertStringNotContainsString('Local Ymir deployment docker images for the "project-with-images" project deleted successfully', $tester->getDisplay());
    }

    public function testDeleteAllImages(): void
    {
        $this->setupActiveTeam();

        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with('dkr.ecr')->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, ['--all' => true], ['yes']);

        $this->assertStringContainsString('All local Ymir deployment docker images deleted successfully', $tester->getDisplay());
    }

    public function testDeleteAllImagesIgnoresLocalProject(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create([
            'id' => 1,
            'name' => 'project-with-images',
            'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images',
        ]);

        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $this->projectTypeMock = \Mockery::mock(ProjectTypeInterface::class);
        $this->projectTypeMock->shouldReceive('getSlug')->andReturn('wordpress');

        $this->projectConfiguration = new ProjectConfiguration($this->filesystem, [$this->projectTypeMock], $this->tempDir.'/ymir.yml');
        $this->projectConfiguration->createNew($project, collect(), $this->projectTypeMock);

        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with('dkr.ecr')->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, ['--all' => true], ['yes']);

        $this->assertStringContainsString('All local Ymir deployment docker images deleted successfully', $tester->getDisplay());
    }

    public function testDeleteProjectImages(): void
    {
        $team = $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'project-with-images', 'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images']);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project]));
        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with($project->getRepositoryUri())->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, [], ['1', 'yes']);

        $this->assertStringContainsString('Local Ymir deployment docker images for the "project-with-images" project deleted successfully', $tester->getDisplay());
    }

    public function testDeleteProjectImagesFailsIfNoRepositoryUri(): void
    {
        $team = $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'project-without-images', 'repository_uri' => null]);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project]));

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $this->expectExceptionMessage('The "project-without-images" project hasn\'t been deployed using container images');

        $this->executeCommand(DeleteDockerImagesCommand::NAME, [], ['1']);
    }

    public function testDeleteProjectImagesInProjectDirectory(): void
    {
        $this->setupActiveTeam();
        $project = ProjectFactory::create([
            'id' => 1,
            'name' => 'project-with-images',
            'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images',
        ]);

        $this->apiClient->shouldReceive('getProject')->with(1)->andReturn($project);

        $this->projectTypeMock = \Mockery::mock(ProjectTypeInterface::class);
        $this->projectTypeMock->shouldReceive('getSlug')->andReturn('wordpress');

        $this->projectConfiguration = new ProjectConfiguration($this->filesystem, [$this->projectTypeMock], $this->tempDir.'/ymir.yml');
        $this->projectConfiguration->createNew($project, collect(), $this->projectTypeMock);

        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with($project->getRepositoryUri())->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, [], ['yes']);

        $this->assertStringContainsString('Local Ymir deployment docker images for the "project-with-images" project deleted successfully', $tester->getDisplay());
    }

    public function testDeleteProjectImagesUsingArgument(): void
    {
        $team = $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'project-with-images', 'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images']);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project]));
        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with($project->getRepositoryUri())->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, ['project' => '1'], ['yes']);

        $this->assertStringContainsString('Local Ymir deployment docker images for the "project-with-images" project deleted successfully', $tester->getDisplay());
    }

    public function testDeleteProjectImagesUsingNameArgument(): void
    {
        $team = $this->setupActiveTeam();
        $project = ProjectFactory::create(['id' => 1, 'name' => 'project-with-images', 'repository_uri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/project-with-images']);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project]));
        $this->dockerExecutable->shouldReceive('removeImagesMatchingPattern')->with($project->getRepositoryUri())->once();

        $this->bootApplication([new DeleteDockerImagesCommand($this->apiClient, $this->createExecutionContextFactory([
            Project::class => function () { return new ProjectDefinition(); },
        ]), $this->dockerExecutable)]);

        $tester = $this->executeCommand(DeleteDockerImagesCommand::NAME, ['project' => 'project-with-images'], ['yes']);

        $this->assertStringContainsString('Local Ymir deployment docker images for the "project-with-images" project deleted successfully', $tester->getDisplay());
    }
}
