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
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionDatabaseServerRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class ResolveOrProvisionDatabaseServerRequirementTest extends TestCase
{
    public function testFulfillProvisionsDatabaseServerIfNoneFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getProvisioner')->andReturn($provisioner);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $input->shouldReceive('hasOption')->with('server')->andReturn(false);

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([]));

        $provisioner->shouldReceive('provision')->once()
                    ->with(\Mockery::type(\Ymir\Cli\Resource\Definition\DatabaseServerDefinition::class), $context, ['pre' => 'filled'])
                    ->andReturn($server);

        $requirement = new ResolveOrProvisionDatabaseServerRequirement('Which server?', ['pre' => 'filled']);

        $this->assertSame($server, $requirement->fulfill($context));
    }

    public function testFulfillReturnsDatabaseServerIfFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['name' => 'server-name']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('server')->andReturn('server-name');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $requirement = new ResolveOrProvisionDatabaseServerRequirement('Which server?');

        $this->assertSame($server, $requirement->fulfill($context));
    }
}
