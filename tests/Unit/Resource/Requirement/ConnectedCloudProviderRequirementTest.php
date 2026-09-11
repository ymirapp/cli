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
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\ConnectedCloudProviderRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class ConnectedCloudProviderRequirementTest extends TestCase
{
    public function testFulfillAlwaysRequiresConnectedProvider(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $provider = CloudProviderFactory::create(['id' => 123, 'status' => 'pending']);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getTeam')->andReturn($team);
        $input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "name" cloud provider connection (ID: 123) cannot be used for operations because its status is "pending"');

        (new ConnectedCloudProviderRequirement('Which provider?'))->fulfill($context, ['status' => 'pending']);
    }
}
