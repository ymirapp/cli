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

use Illuminate\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\RegionRequirement;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class RegionRequirementTest extends TestCase
{
    public function testFulfillReturnsRegionFromInteractiveChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $provider = CloudProviderFactory::create();
        $regions = new Collection(['us-east-1' => 'US East (N. Virginia)', 'us-west-2' => 'US West (Oregon)']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getProject')->andReturn(null);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getStringOption')->with('region', true)->andReturn(null);

        $apiClient->shouldReceive('getRegions')->with($provider)->andReturn($regions);

        $output->shouldReceive('choice')->with('Which region?', $regions)->andReturn('us-west-2');

        $requirement = new RegionRequirement('Which region?');

        $this->assertSame('us-west-2', $requirement->fulfill($context, ['provider' => $provider]));
    }

    public function testFulfillReturnsRegionFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $provider = CloudProviderFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('region', true)->andReturn('us-east-1');

        $apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));

        $requirement = new RegionRequirement('Which region?');

        $this->assertSame('us-east-1', $requirement->fulfill($context, ['provider' => $provider]));
    }

    public function testFulfillReturnsRegionFromProjectFallback(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $project = ProjectFactory::create(['region' => 'us-east-1']);
        $provider = CloudProviderFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getProject')->andReturn($project);

        $input->shouldReceive('getStringOption')->with('region', true)->andReturn(null);

        $apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));

        $requirement = new RegionRequirement('Which region?');

        $this->assertSame('us-east-1', $requirement->fulfill($context, ['provider' => $provider]));
    }

    public function testFulfillThrowsExceptionIfInvalidRegionProvided(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $provider = CloudProviderFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The given "region" isn\'t a valid cloud provider region');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('region', true)->andReturn('invalid-region');

        $apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));

        $requirement = new RegionRequirement('Which region?');
        $requirement->fulfill($context, ['provider' => $provider]);
    }

    public function testFulfillThrowsExceptionIfNoRegionsFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $provider = CloudProviderFactory::create();

        $this->expectException(RequirementFulfillmentException::class);
        $this->expectExceptionMessage('no cloud provider regions found');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('region', true)->andReturn(null);

        $apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection());

        $requirement = new RegionRequirement('Which region?');
        $requirement->fulfill($context, ['provider' => $provider]);
    }

    public function testFulfillThrowsExceptionWhenProviderMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"provider" must be fulfilled before fulfilling the region requirement');

        $requirement = new RegionRequirement('Which region?');

        $requirement->fulfill($context, []);
    }
}
