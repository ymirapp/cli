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
use Ymir\Cli\Resource\Definition\CacheClusterDefinition;
use Ymir\Cli\Resource\Requirement\CacheClusterEngineRequirement;
use Ymir\Cli\Resource\Requirement\CacheClusterTypeRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\NatGatewayRequirement;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionNetworkRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CacheClusterDefinitionTest extends TestCase
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
        $definition = new CacheClusterDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(5, $requirements);
        $this->assertInstanceOf(NameSlugRequirement::class, $requirements['name']);
        $this->assertInstanceOf(ResolveOrProvisionNetworkRequirement::class, $requirements['network']);
        $this->assertInstanceOf(NatGatewayRequirement::class, $requirements['nat_gateway']);
        $this->assertInstanceOf(CacheClusterEngineRequirement::class, $requirements['engine']);
        $this->assertInstanceOf(CacheClusterTypeRequirement::class, $requirements['type']);
    }

    public function testProvision(): void
    {
        $cacheCluster = CacheClusterFactory::create();
        $network = NetworkFactory::create();

        $this->apiClient->shouldReceive('createCache')->once()
                  ->with($network, 'name', 'engine', 'type')
                  ->andReturn($cacheCluster);

        $definition = new CacheClusterDefinition();

        $this->assertSame($cacheCluster, $definition->provision($this->apiClient, [
            'network' => $network,
            'name' => 'name',
            'engine' => 'engine',
            'type' => 'type',
        ]));
    }

    public function testResolveFiltersByRegion(): void
    {
        $cacheUsEast1 = CacheClusterFactory::create(['id' => 1, 'name' => 'east-cache', 'region' => 'us-east-1']);
        $cacheUsWest2 = CacheClusterFactory::create(['id' => 2, 'name' => 'west-cache', 'region' => 'us-west-2']);

        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([$cacheUsEast1, $cacheUsWest2]));

        $this->output->shouldReceive('choiceWithResourceDetails')->with('question', \Mockery::on(function ($caches) {
            return 1 === $caches->count() && 'us-west-2' === $caches->first()->getRegion();
        }))->andReturn('west-cache');

        $definition = new CacheClusterDefinition();

        $this->assertSame($cacheUsWest2, $definition->resolve($this->context, 'question', ['region' => 'us-west-2']));
    }

    public function testResolveThrowsExceptionIfCacheClusterNotFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([CacheClusterFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a cache cluster with "non-existent" as the ID or name');

        $definition = new CacheClusterDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfCacheIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([CacheClusterFactory::create()]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid cache cluster ID or name');

        $definition = new CacheClusterDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNameCollision(): void
    {
        $cache1 = CacheClusterFactory::create(['id' => 1, 'name' => 'duplicate']);
        $cache2 = CacheClusterFactory::create(['id' => 2, 'name' => 'duplicate']);

        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('duplicate');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([$cache1, $cache2]));

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a cache cluster because more than one cache cluster has the name "duplicate"');

        $definition = new CacheClusterDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoCacheClustersFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no cache clusters, but you can create one with the "cache:create" command');

        $definition = new CacheClusterDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $cacheCluster = CacheClusterFactory::create(['name' => 'my-cache']);

        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('my-cache');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([$cacheCluster]));

        $definition = new CacheClusterDefinition();

        $this->assertSame($cacheCluster, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $cacheCluster = CacheClusterFactory::create(['name' => 'choice-cache']);

        $this->input->shouldReceive('getStringArgument')->with('cache')->andReturn('');
        $this->apiClient->shouldReceive('getCaches')->andReturn(new ResourceCollection([$cacheCluster]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('choice-cache');

        $definition = new CacheClusterDefinition();

        $this->assertSame($cacheCluster, $definition->resolve($this->context, 'question'));
    }
}
