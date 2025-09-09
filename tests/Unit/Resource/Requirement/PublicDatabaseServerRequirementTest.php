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
use Ymir\Cli\Resource\Requirement\PublicDatabaseServerRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class PublicDatabaseServerRequirementTest extends TestCase
{
    public function testFulfillReturnsDatabaseServerIfPublic(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['name' => 'server', 'publicly_accessible' => true]);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('server')->andReturn('server');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $requirement = new PublicDatabaseServerRequirement('Which server?');

        $this->assertSame($server, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfDatabaseServerIsNotPublic(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['name' => 'server', 'publicly_accessible' => false]);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Please select a public database server');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('server')->andReturn('server');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $requirement = new PublicDatabaseServerRequirement('Which server?');

        $requirement->fulfill($context);
    }
}
