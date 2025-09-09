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
use Ymir\Cli\Resource\Requirement\CacheClusterTypeRequirement;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\TestCase;

class CacheClusterTypeRequirementTest extends TestCase
{
    public function testFulfillReturnsTypeFromChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $apiClient->shouldReceive('getCacheTypes')->with($network->getProvider())->andReturn(new Collection([
            'cache.t3.micro' => ['cpu' => 2, 'ram' => 0.5, 'price' => ['redis' => 10]],
        ]));

        $output->shouldReceive('choice')->with('Which type?', \Mockery::on(function ($collection) {
            return $collection->has('cache.t3.micro') && str_contains($collection->get('cache.t3.micro'), '2 vCPU, 0.5GiB RAM (~$10/month)');
        }))->andReturn('cache.t3.micro');

        $requirement = new CacheClusterTypeRequirement('Which type?');

        $this->assertSame('cache.t3.micro', $requirement->fulfill($context, ['engine' => 'redis', 'network' => $network]));
    }

    public function testFulfillReturnsTypeFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('type')->andReturn('cache.t3.micro');

        $apiClient->shouldReceive('getCacheTypes')->with($network->getProvider())->andReturn(new Collection([
            'cache.t3.micro' => ['cpu' => 2, 'ram' => 0.5, 'price' => ['redis' => 10]],
        ]));

        $requirement = new CacheClusterTypeRequirement('Which type?');

        $this->assertSame('cache.t3.micro', $requirement->fulfill($context, ['engine' => 'redis', 'network' => $network]));
    }

    public function testFulfillThrowsExceptionIfEngineRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"engine" must be fulfilled before fulfilling the cache cluster type requirement');

        $requirement = new CacheClusterTypeRequirement('Which type?');
        $requirement->fulfill($context, []);
    }

    public function testFulfillThrowsExceptionIfInvalidTypeProvided(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The type "invalid" isn\'t a valid cache cluster type');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('type')->andReturn('invalid');

        $apiClient->shouldReceive('getCacheTypes')->with($network->getProvider())->andReturn(new Collection([
            'cache.t3.micro' => ['cpu' => 2, 'ram' => 0.5, 'price' => ['redis' => 10]],
        ]));

        $requirement = new CacheClusterTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => 'redis', 'network' => $network]);
    }

    public function testFulfillThrowsExceptionIfNetworkRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"network" must be fulfilled before fulfilling the cache cluster type requirement');

        $requirement = new CacheClusterTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => 'redis']);
    }

    public function testFulfillThrowsExceptionIfNoCacheTypesFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $this->expectException(RequirementFulfillmentException::class);
        $this->expectExceptionMessage('no cache cluster types found');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $apiClient->shouldReceive('getCacheTypes')->with($network->getProvider())->andReturn(new Collection());

        $requirement = new CacheClusterTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => 'redis', 'network' => $network]);
    }
}
