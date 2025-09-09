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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CloudProviderRequirementTest extends TestCase
{
    public function testFulfillReturnsCloudProviderFromArgument(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create(['id' => 1]);
        $provider = CloudProviderFactory::create(['id' => 123]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $requirement = new CloudProviderRequirement('Which provider?');

        $this->assertSame($provider->getId(), $requirement->fulfill($context)->getId());
    }

    public function testFulfillReturnsCloudProviderFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create(['id' => 1]);
        $provider = CloudProviderFactory::create(['id' => 123]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $input->shouldReceive('hasOption')->with('provider')->andReturn(true);
        $input->shouldReceive('getNumericOption')->with('provider')->andReturn(123);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $requirement = new CloudProviderRequirement('Which provider?');

        $this->assertSame($provider->getId(), $requirement->fulfill($context)->getId());
    }

    public function testFulfillReturnsProjectProviderAsFallback(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create(['id' => 1]);
        $project = ProjectFactory::create();
        $provider = $project->getProvider();

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);
        $context->shouldReceive('getProject')->andReturn($project);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $input->shouldReceive('hasOption')->with('provider')->andReturn(false);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $requirement = new CloudProviderRequirement('Which provider?');

        $this->assertSame($provider->getId(), $requirement->fulfill($context)->getId());
    }

    public function testFulfillReturnsProviderFromInteractiveChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $team = TeamFactory::create(['id' => 1]);
        $provider = CloudProviderFactory::create(['id' => 123, 'name' => 'AWS']);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);
        $context->shouldReceive('getProject')->andReturn(null);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $input->shouldReceive('hasOption')->with('provider')->andReturn(false);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $output->shouldReceive('choiceWithId')->once()->andReturn(123);

        $requirement = new CloudProviderRequirement('Which provider?');

        $this->assertSame($provider->getId(), $requirement->fulfill($context)->getId());
    }

    public function testFulfillThrowsExceptionIfInvalidProviderIdProvided(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create(['id' => 1]);
        $provider = CloudProviderFactory::create(['id' => 123]);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The given provider "456" isn\'t available to the currently active team');

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $input->shouldReceive('getNumericArgument')->with('provider')->andReturn(456);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $requirement = new CloudProviderRequirement('Which provider?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfNoProvidersFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create(['id' => 1]);

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no cloud providers');

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $input->shouldReceive('hasOption')->with('provider')->andReturn(false);

        $apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([]));

        $requirement = new CloudProviderRequirement('Which provider?');
        $requirement->fulfill($context);
    }
}
