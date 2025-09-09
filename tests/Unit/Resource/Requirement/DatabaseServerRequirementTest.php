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
use Ymir\Cli\Resource\Requirement\DatabaseServerRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerRequirementTest extends TestCase
{
    public function testFulfillReturnsDatabaseServerFromArgument(): void
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

        $requirement = new DatabaseServerRequirement('Which server?');

        $this->assertSame($server, $requirement->fulfill($context));
    }

    public function testFulfillReturnsDatabaseServerFromInteractiveChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['id' => 123]);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $input->shouldReceive('hasOption')->with('server')->andReturn(false);

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $output->shouldReceive('choiceWithResourceDetails')->with('Which server?', \Mockery::type(ResourceCollection::class))->andReturn('123');

        $requirement = new DatabaseServerRequirement('Which server?');

        $this->assertSame($server, $requirement->fulfill($context));
    }

    public function testFulfillReturnsDatabaseServerFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['name' => 'server-name']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $input->shouldReceive('hasOption')->with('server')->andReturn(true);
        $input->shouldReceive('getStringOption')->with('server', true)->andReturn('server-name');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $requirement = new DatabaseServerRequirement('Which server?');

        $this->assertSame($server, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfInteractiveChoiceReturnsEmpty(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid database server ID or name');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $input->shouldReceive('hasOption')->with('server')->andReturn(false);

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $requirement = new DatabaseServerRequirement('Which server?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfMultipleServersWithSameNameFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server1 = DatabaseServerFactory::create(['id' => 1, 'name' => 'server']);
        $server2 = DatabaseServerFactory::create(['id' => 2, 'name' => 'server']);

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a database server because more than one database server has the name "server"');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('server')->andReturn('server');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server1, $server2]));

        $requirement = new DatabaseServerRequirement('Which server?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfNoDatabaseServersFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no database servers');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $input->shouldReceive('hasOption')->with('server')->andReturn(false);

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([]));

        $requirement = new DatabaseServerRequirement('Which server?');
        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfServerNotFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $team = TeamFactory::create();
        $server = DatabaseServerFactory::create(['id' => 1]);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a database server with "2" as the ID or name');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getTeam')->andReturn($team);

        $input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('server')->andReturn('2');

        $apiClient->shouldReceive('getDatabaseServers')->with($team)->andReturn(new ResourceCollection([$server]));

        $requirement = new DatabaseServerRequirement('Which server?');
        $requirement->fulfill($context);
    }
}
