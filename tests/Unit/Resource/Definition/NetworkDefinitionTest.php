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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class NetworkDefinitionTest extends TestCase
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
        $definition = new NetworkDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(3, $requirements);
        $this->assertInstanceOf(NameSlugRequirement::class, $requirements['name']);
        $this->assertInstanceOf(CloudProviderRequirement::class, $requirements['provider']);
        $this->assertInstanceOf(RegionRequirement::class, $requirements['region']);
    }

    public function testProvision(): void
    {
        $network = NetworkFactory::create();

        $this->apiClient->shouldReceive('createNetwork')->once()
                  ->with($network->getProvider(), 'name', 'region')
                  ->andReturn($network);

        $definition = new NetworkDefinition();

        $this->assertSame($network, $definition->provision($this->apiClient, [
            'provider' => $network->getProvider(),
            'name' => 'name',
            'region' => 'region',
        ]));
    }

    public function testResolveThrowsExceptionIfNameCollision(): void
    {
        $network1 = NetworkFactory::create(['id' => 1, 'name' => 'duplicate']);
        $network2 = NetworkFactory::create(['id' => 2, 'name' => 'duplicate']);

        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('network')->andReturn('duplicate');
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([$network1, $network2]));

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a network because more than one network has the name "duplicate"');

        $definition = new NetworkDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNetworkIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('network')->andReturn(false);
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([NetworkFactory::create()]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid network ID or name');

        $definition = new NetworkDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNetworkNotFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('network')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([NetworkFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a network with "non-existent" as the ID or name');

        $definition = new NetworkDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoNetworksFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('network')->andReturn(false);
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no networks, but you can create one with the "network:create" command');

        $definition = new NetworkDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $network = NetworkFactory::create(['name' => 'my-network']);

        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('network')->andReturn('my-network');
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([$network]));

        $definition = new NetworkDefinition();

        $this->assertSame($network, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $network = NetworkFactory::create(['name' => 'choice-network']);

        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('network')->andReturn(false);
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([$network]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('choice-network');

        $definition = new NetworkDefinition();

        $this->assertSame($network, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $network = NetworkFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('network')->andReturn(true);
        $this->input->shouldReceive('getStringOption')->with('network', true)->andReturn('123');
        $this->apiClient->shouldReceive('getNetworks')->andReturn(new ResourceCollection([$network]));

        $definition = new NetworkDefinition();

        $this->assertSame($network, $definition->resolve($this->context, 'question'));
    }
}
