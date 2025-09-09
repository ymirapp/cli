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

namespace Ymir\Cli\Tests\Unit\Resource\Requirement;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionNetworkRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class ResolveOrProvisionNetworkRequirementTest extends TestCase
{
    public function testFulfillProvisionsNetworkIfNoneFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getProvisioner')->andReturn($provisioner);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $input->shouldReceive('hasOption')->with('network')->andReturn(false);

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([]));

        $provisioner->shouldReceive('provision')->once()
                    ->with(\Mockery::type(\Ymir\Cli\Resource\Definition\NetworkDefinition::class), $context, [])
                    ->andReturn($network);

        $requirement = new ResolveOrProvisionNetworkRequirement('Which network?');

        $this->assertSame($network, $requirement->fulfill($context));
    }

    public function testFulfillReturnsNetworkIfFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create(['name' => 'network-name']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('network')->andReturn('network-name');

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $requirement = new ResolveOrProvisionNetworkRequirement('Which network?');

        $this->assertSame($network, $requirement->fulfill($context));
    }
}
