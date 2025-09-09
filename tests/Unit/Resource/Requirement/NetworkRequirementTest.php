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
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\NetworkRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class NetworkRequirementTest extends TestCase
{
    public function testFulfillReturnsNetworkFromArgument(): void
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

        $requirement = new NetworkRequirement('Which network?');

        $this->assertSame($network, $requirement->fulfill($context));
    }

    public function testFulfillReturnsNetworkFromInteractiveChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create(['id' => 123]);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $input->shouldReceive('hasOption')->with('network')->andReturn(false);

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $output->shouldReceive('choiceWithResourceDetails')->with('Which network?', \Mockery::type(ResourceCollection::class))->andReturn('123');

        $requirement = new NetworkRequirement('Which network?');

        $this->assertSame($network, $requirement->fulfill($context));
    }

    public function testFulfillReturnsNetworkFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create(['name' => 'network-name']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $input->shouldReceive('hasOption')->with('network')->andReturn(true);
        $input->shouldReceive('getStringOption')->with('network', true)->andReturn('network-name');

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $requirement = new NetworkRequirement('Which network?');

        $this->assertSame($network, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfInteractiveChoiceReturnsEmpty(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid network ID or name');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $input->shouldReceive('hasOption')->with('network')->andReturn(false);

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $requirement = new NetworkRequirement('Which network?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfMultipleNetworksWithSameNameFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $network1 = NetworkFactory::create(['id' => 1, 'name' => 'network']);
        $network2 = NetworkFactory::create(['id' => 2, 'name' => 'network']);

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a network because more than one network has the name "network"');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('network')->andReturn('network');

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network1, $network2]));

        $requirement = new NetworkRequirement('Which network?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfNetworkNotFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $network = NetworkFactory::create(['id' => 1]);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a network with "2" as the ID or name');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('network')->andReturn('2');

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $requirement = new NetworkRequirement('Which network?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfNoNetworksFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no networks');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('network')->andReturn(false);
        $input->shouldReceive('hasOption')->with('network')->andReturn(false);

        $apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([]));

        $requirement = new NetworkRequirement('Which network?');
        $requirement->fulfill($context);
    }
}
