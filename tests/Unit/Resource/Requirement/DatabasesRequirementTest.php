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
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\DatabasesRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\TestCase;

class DatabasesRequirementTest extends TestCase
{
    public function testFulfillReturnsDatabasesFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $server = DatabaseServerFactory::create();

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getArrayArgument')->with('databases', false)->andReturn(['db1', 'db2']);

        $requirement = new DatabasesRequirement();

        $this->assertSame(['db1', 'db2'], $requirement->fulfill($context, ['server' => $server, 'user' => 'user']));
    }

    public function testFulfillReturnsEmptyArrayForPrivateServer(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $server = DatabaseServerFactory::create(['publicly_accessible' => false]);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getArrayArgument')->with('databases', false)->andReturn([]);

        $requirement = new DatabasesRequirement();

        $this->assertSame([], $requirement->fulfill($context, ['server' => $server, 'user' => 'user']));
    }

    public function testFulfillReturnsEmptyArrayWhenPublicServerAccessConfirmed(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $server = DatabaseServerFactory::create(['publicly_accessible' => true]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getArrayArgument')->with('databases', false)->andReturn([]);

        $output->shouldReceive('confirm')->with('Do you want the "<comment>user</comment>" user to have access to all databases?', false)->andReturn(true);

        $requirement = new DatabasesRequirement();

        $this->assertSame([], $requirement->fulfill($context, ['server' => $server, 'user' => 'user']));
    }

    public function testFulfillReturnsSelectedDatabasesWhenPublicServerAccessDenied(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $database = DatabaseFactory::create(['name' => 'db1']);
        $server = DatabaseServerFactory::create(['publicly_accessible' => true]);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getArrayArgument')->with('databases', false)->andReturn([]);

        $output->shouldReceive('confirm')->with('Do you want the "<comment>user</comment>" user to have access to all databases?', false)->andReturn(false);

        $apiClient->shouldReceive('getDatabases')->with($server)->andReturn(new ResourceCollection([$database]));

        $output->shouldReceive('multichoice')->with('Which databases should the "<comment>user</comment>" database user have access to? (Use a comma-separated list)', \Mockery::on(function ($collection) {
            return $collection->contains('db1');
        }))->andReturn(['db1']);

        $requirement = new DatabasesRequirement();

        $this->assertSame(['db1'], $requirement->fulfill($context, ['server' => $server, 'user' => 'user']));
    }

    public function testFulfillThrowsExceptionWhenDependenciesMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"server" and "user" must be fulfilled before fulfilling the databases requirement');

        $requirement = new DatabasesRequirement();

        $requirement->fulfill($context, []);
    }
}
