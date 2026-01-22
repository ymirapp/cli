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

namespace Ymir\Cli\Tests\Unit\Resource\Definition;

use Illuminate\Support\Enumerable;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ProjectDefinition;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\EnvironmentsRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class ProjectDefinitionTest extends TestCase
{
    /**
     * @var ApiClient|\Mockery\MockInterface
     */
    private $apiClient;

    /**
     * @var ExecutionContext|\Mockery\MockInterface
     */
    private $context;

    /**
     * @var Input|\Mockery\MockInterface
     */
    private $input;

    /**
     * @var \Mockery\MockInterface|Output
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = \Mockery::mock(ApiClient::class);
        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->input = \Mockery::mock(Input::class);
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getApiClient')->andReturn($this->apiClient);
        $this->context->shouldReceive('getInput')->andReturn($this->input);
        $this->context->shouldReceive('getOutput')->andReturn($this->output);
        $this->context->shouldReceive('getTeam')->andReturn(TeamFactory::create());
    }

    public function testGetRequirements(): void
    {
        $definition = new ProjectDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(4, $requirements);
        $this->assertInstanceOf(NameSlugRequirement::class, $requirements['name']);
        $this->assertInstanceOf(CloudProviderRequirement::class, $requirements['provider']);
        $this->assertInstanceOf(RegionRequirement::class, $requirements['region']);
        $this->assertInstanceOf(EnvironmentsRequirement::class, $requirements['environments']);
    }

    public function testProvision(): void
    {
        $provider = CloudProviderFactory::create();
        $project = ProjectFactory::create();

        $this->apiClient->shouldReceive('createProject')->once()
                  ->with($provider, 'name', 'region', ['staging'])
                  ->andReturn($project);

        $definition = new ProjectDefinition();

        $this->assertSame($project, $definition->provision($this->apiClient, [
            'provider' => $provider,
            'name' => 'name',
            'region' => 'region',
            'environments' => ['staging'],
        ]));
    }

    public function testResolveReturnsProjectFromContextIfNoArgumentProvided(): void
    {
        $project = ProjectFactory::create();
        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(false);
        $this->context->shouldReceive('getProject')->andReturn($project);

        $definition = new ProjectDefinition();

        $this->assertSame($project, $definition->resolve($this->context, 'question'));
    }

    public function testResolveThrowsExceptionIfNameCollision(): void
    {
        $project1 = ProjectFactory::create(['id' => 1, 'name' => 'duplicate']);
        $project2 = ProjectFactory::create(['id' => 2, 'name' => 'duplicate']);

        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('project')->andReturn('duplicate');
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project1, $project2]));

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a project because more than one project has the name "duplicate"');

        $definition = new ProjectDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoProjectsFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(false);
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no projects, but you can create one with the "project:init" command');

        $definition = new ProjectDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProjectIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(false);
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([ProjectFactory::create()]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid project ID or name');

        $definition = new ProjectDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProjectNotFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('project')->andReturn('non-existent');
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([ProjectFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a project with "non-existent" as the ID or name');

        $definition = new ProjectDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $project = ProjectFactory::create(['name' => 'my-project']);

        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('project')->andReturn('my-project');
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));

        $definition = new ProjectDefinition();

        $this->assertSame($project, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $project = ProjectFactory::create(['id' => 123, 'name' => 'choice-project']);

        $this->input->shouldReceive('hasArgument')->with('project')->andReturn(false);
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->apiClient->shouldReceive('getProjects')->andReturn(new ResourceCollection([$project]));
        $this->output->shouldReceive('choiceWithResourceDetails')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice-project');

        $definition = new ProjectDefinition();

        $this->assertSame($project, $definition->resolve($this->context, 'question'));
    }
}
