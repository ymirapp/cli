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

namespace Ymir\Cli\Tests\Unit\Project\Initialization;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\CacheConfigurationChange;
use Ymir\Cli\Project\Initialization\CacheInitializationStep;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CacheInitializationStepTest extends TestCase
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
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getApiClient')->andReturn($this->apiClient);
        $this->context->shouldReceive('getOutput')->andReturn($this->output);
        $this->context->shouldReceive('getTeam')->andReturn(TeamFactory::create());
    }

    public function testPerformFiltersCacheClustersByRegionAndStatus(): void
    {
        $differentRegionCluster = CacheClusterFactory::create(['name' => 'different-region', 'region' => 'us-west-2']);
        $deletingCluster = CacheClusterFactory::create(['name' => 'deleting', 'status' => 'deleting', 'region' => 'us-east-1']);
        $failedCluster = CacheClusterFactory::create(['name' => 'failed', 'status' => 'failed', 'region' => 'us-east-1']);

        $this->apiClient->shouldReceive('getCaches')->once()->andReturn(new ResourceCollection([
            $differentRegionCluster,
            $deletingCluster,
            $failedCluster,
        ]));
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with(\Mockery::pattern('/Your team doesn\'t have any configured cache clusters/'))->once()->andReturn(false);

        $step = new CacheInitializationStep();

        $this->assertNull($step->perform($this->context, ['region' => 'us-east-1']));
    }

    public function testPerformProvisionsNewCacheClusterIfRequestedWhenNoneExist(): void
    {
        $cacheCluster = CacheClusterFactory::create(['name' => 'new-cache-cluster']);

        $this->apiClient->shouldReceive('getCaches')->once()->andReturn(new ResourceCollection([]));
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with(\Mockery::pattern('/Your team doesn\'t have any configured cache clusters/'))->once()->andReturn(true);
        $this->context->shouldReceive('provision')->with(CacheCluster::class)->once()->andReturn($cacheCluster);

        $step = new CacheInitializationStep();

        $result = $step->perform($this->context, ['region' => 'us-east-1']);

        $this->assertInstanceOf(CacheConfigurationChange::class, $result);
    }

    public function testPerformProvisionsNewCacheClusterIfRequestedWhenSomeExist(): void
    {
        $cacheCluster = CacheClusterFactory::create(['name' => 'new-cache-cluster']);
        $existingCacheCluster = CacheClusterFactory::create(['name' => 'existing-cache-cluster', 'region' => 'us-east-1']);

        $this->apiClient->shouldReceive('getCaches')->once()->andReturn(new ResourceCollection([$existingCacheCluster]));
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with('Would you like to use an existing cache cluster for this project?')->once()->andReturn(false);
        $this->output->shouldReceive('confirm')->with('Would you like to create a new one for this project instead?')->once()->andReturn(true);
        $this->context->shouldReceive('provision')->with(CacheCluster::class)->once()->andReturn($cacheCluster);

        $step = new CacheInitializationStep();

        $result = $step->perform($this->context, ['region' => 'us-east-1']);

        $this->assertInstanceOf(CacheConfigurationChange::class, $result);
    }

    public function testPerformReturnsCacheConfigurationChangeIfExistingCacheClusterSelected(): void
    {
        $cacheCluster = CacheClusterFactory::create(['name' => 'existing-cache-cluster', 'region' => 'us-east-1']);

        $this->apiClient->shouldReceive('getCaches')->once()->andReturn(new ResourceCollection([$cacheCluster]));
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with('Would you like to use an existing cache cluster for this project?')->once()->andReturn(true);
        $this->output->shouldReceive('choiceWithResourceDetails')->once()->andReturn('existing-cache-cluster');

        $step = new CacheInitializationStep();

        $result = $step->perform($this->context, ['region' => 'us-east-1']);

        $this->assertInstanceOf(CacheConfigurationChange::class, $result);
    }

    public function testPerformReturnsNullIfExistingClustersButNoneSelected(): void
    {
        $existingCacheCluster = CacheClusterFactory::create(['name' => 'existing-cache-cluster', 'region' => 'us-east-1']);

        $this->apiClient->shouldReceive('getCaches')->once()->andReturn(new ResourceCollection([$existingCacheCluster]));
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with('Would you like to use an existing cache cluster for this project?')->once()->andReturn(false);
        $this->output->shouldReceive('confirm')->with('Would you like to create a new one for this project instead?')->once()->andReturn(false);

        $step = new CacheInitializationStep();

        $this->assertNull($step->perform($this->context, ['region' => 'us-east-1']));
    }

    public function testPerformReturnsNullIfNoCacheClusterSelected(): void
    {
        $this->output->shouldReceive('confirm')->with('Would you like to use a cache cluster for this project?', false)->once()->andReturn(false);

        $step = new CacheInitializationStep();

        $this->assertNull($step->perform($this->context, ['region' => 'us-east-1']));
    }
}
