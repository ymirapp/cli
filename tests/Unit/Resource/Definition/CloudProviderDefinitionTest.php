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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Requirement\ActiveTeamRequirement;
use Ymir\Cli\Resource\Requirement\AwsCredentialsRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CloudProviderDefinitionTest extends TestCase
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
        $definition = new CloudProviderDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(3, $requirements);
        $this->assertInstanceOf(ActiveTeamRequirement::class, $requirements['active_team']);
        $this->assertInstanceOf(NameRequirement::class, $requirements['name']);
        $this->assertInstanceOf(AwsCredentialsRequirement::class, $requirements['credentials']);
    }

    public function testProvision(): void
    {
        $cloudProvider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->once()
                  ->with($cloudProvider->getTeam(), 'name', ['key' => 'value'])
                  ->andReturn($cloudProvider);

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->provision($this->apiClient, [
            'active_team' => $cloudProvider->getTeam(),
            'name' => 'name',
            'credentials' => ['key' => 'value'],
        ]));
    }

    public function testResolveReturnsProviderFromProjectIfNoIdProvided(): void
    {
        $cloudProvider = CloudProviderFactory::create();
        $project = ProjectFactory::create();

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));
        $this->context->shouldReceive('getProject')->andReturn($project);

        $definition = new CloudProviderDefinition();

        $this->assertSame($project->getProvider(), $definition->resolve($this->context, 'question'));
    }

    public function testResolveThrowsExceptionIfNoProvidersFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no cloud providers, but you can connect one with the "provider:connect" command');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProviderNotFoundAfterChoice(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([CloudProviderFactory::create()]));
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldReceive('choiceWithId')->with('question', \Mockery::type(Enumerable::class))->andReturn(123);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a cloud provider with "123" as the ID or name');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProviderNotFoundWhenIdProvided(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([CloudProviderFactory::create(['id' => 456])]));

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The given provider "123" isn\'t available to the currently active team');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123, 'name' => 'choice-provider']);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldReceive('choiceWithId')->with('question', \Mockery::type(Enumerable::class))->andReturn(123);

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericOption')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }
}
