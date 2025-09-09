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
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\ProjectRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class EnvironmentDefinitionTest extends TestCase
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
    }

    public function testGetRequirements(): void
    {
        $definition = new EnvironmentDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(2, $requirements);
        $this->assertInstanceOf(ProjectRequirement::class, $requirements['project']);
        $this->assertInstanceOf(NameSlugRequirement::class, $requirements['name']);
    }

    public function testProvision(): void
    {
        $project = ProjectFactory::create();
        $environment = EnvironmentFactory::create(['project' => $project]);

        $this->apiClient->shouldReceive('createEnvironment')->once()
                  ->with($project, 'name')
                  ->andReturn($environment);

        $definition = new EnvironmentDefinition();

        $this->assertSame($environment, $definition->provision($this->apiClient, [
            'project' => $project,
            'name' => 'name',
        ]));
    }

    public function testResolveThrowsExceptionIfEnvironmentNameIsEmptyAfterChoice(): void
    {
        $project = ProjectFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([EnvironmentFactory::create()]));
        $this->input->shouldReceive('hasArgument')->with('environment')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('environment')->andReturn(false);
        $this->output->shouldReceive('choice')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid environment name');

        $definition = new EnvironmentDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfEnvironmentNotFound(): void
    {
        $project = ProjectFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->input->shouldReceive('hasArgument')->with('environment')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('environment')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([EnvironmentFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a environment with "non-existent" as the ID or name');

        $definition = new EnvironmentDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoEnvironmentsFound(): void
    {
        $project = ProjectFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage(sprintf('The "%s" project has no environments, but you can create one with the "environment:create" command', $project->getName()));

        $definition = new EnvironmentDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoProjectInContext(): void
    {
        $this->context->shouldReceive('getParentResource')->andReturn(null);
        $this->context->shouldReceive('getProject')->andReturn(null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A project must be resolved and passed into the context before resolving an environment');

        $definition = new EnvironmentDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $project = ProjectFactory::create();
        $environment = EnvironmentFactory::create(['name' => 'my-env']);
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->input->shouldReceive('hasArgument')->with('environment')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('environment')->andReturn('my-env');
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));

        $definition = new EnvironmentDefinition();

        $this->assertSame($environment, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $project = ProjectFactory::create();
        $environment = EnvironmentFactory::create(['name' => 'choice-env']);
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->input->shouldReceive('hasArgument')->with('environment')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('environment')->andReturn(false);
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice-env');

        $definition = new EnvironmentDefinition();

        $this->assertSame($environment, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $project = ProjectFactory::create();
        $environment = EnvironmentFactory::create(['name' => 'option-env']);
        $this->context->shouldReceive('getParentResource')->andReturn($project);
        $this->input->shouldReceive('hasArgument')->with('environment')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('environment')->andReturn(true);
        $this->input->shouldReceive('getStringOption')->with('environment', true)->andReturn('option-env');
        $this->apiClient->shouldReceive('getEnvironments')->andReturn(new ResourceCollection([$environment]));

        $definition = new EnvironmentDefinition();

        $this->assertSame($environment, $definition->resolve($this->context, 'question'));
    }
}
