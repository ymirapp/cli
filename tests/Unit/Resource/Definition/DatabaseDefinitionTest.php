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

namespace Ymir\Cli\Tests\Unit\Resource\Definition;

use Illuminate\Support\Enumerable;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\DatabaseDefinition;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionPublicDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\StringArgumentRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseDefinitionTest extends TestCase
{
    /**
     * @var ApiClient|\Mockery\MockInterface
     */
    private $apiClient;

    /**
     * @var ExecutionContext|\Mockery\MockInterface
     */
    private $context;

    /**
     * @var Input|\Mockery\MockInterface
     */
    private $input;

    /**
     * @var \Mockery\MockInterface|Output
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = \Mockery::mock(ApiClient::class);
        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->input = \Mockery::mock(Input::class);
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getApiClient')->andReturn($this->apiClient);
        $this->context->shouldReceive('getInput')->andReturn($this->input);
        $this->context->shouldReceive('getOutput')->andReturn($this->output);
    }

    public function testGetRequirements(): void
    {
        $definition = new DatabaseDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(2, $requirements);
        $this->assertInstanceOf(ResolveOrProvisionPublicDatabaseServerRequirement::class, $requirements['server']);
        $this->assertInstanceOf(StringArgumentRequirement::class, $requirements['database']);
    }

    public function testProvision(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $database = new Database('database', $databaseServer);

        $this->apiClient->shouldReceive('createDatabase')->once()
                  ->with($databaseServer, 'database')
                  ->andReturn($database);

        $definition = new DatabaseDefinition();

        $this->assertSame($database, $definition->provision($this->apiClient, [
            'server' => $databaseServer,
            'database' => 'database',
        ]));
    }

    public function testResolveFiltersSystemDatabases(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([
            new Database('information_schema', $databaseServer),
            new Database('mysql', $databaseServer),
            new Database('my_db', $databaseServer),
        ]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('my_db');

        $definition = new DatabaseDefinition();
        $database = $definition->resolve($this->context, 'question');

        $this->assertSame('my_db', $database->getName());
    }

    public function testResolveReturnsDatabaseIfPrivateServerAndNameProvided(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => false]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('my_db');

        $definition = new DatabaseDefinition();
        $database = $definition->resolve($this->context, 'question');

        $this->assertInstanceOf(Database::class, $database);
        $this->assertSame('my_db', $database->getName());
        $this->assertSame($databaseServer, $database->getDatabaseServer());
    }

    public function testResolveThrowsExceptionIfDatabaseNameIsEmptyAfterChoice(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([new Database('my_db', $databaseServer)]));
        $this->output->shouldReceive('choice')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid database name');

        $definition = new DatabaseDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfDatabaseNotFound(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([new Database('other', $databaseServer)]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a database with "non-existent" as the ID or name');

        $definition = new DatabaseDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDatabaseServerInContext(): void
    {
        $this->context->shouldReceive('getParentResource')->andReturn(null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A DatabaseServer must be resolved and passed into the context before resolving a database');

        $definition = new DatabaseDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDatabasesFoundOnPublicServer(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage(sprintf('The "%s" database server has no databases that can be managed by Ymir', $databaseServer->getName()));

        $definition = new DatabaseDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfPrivateServerAndNoDatabaseNameProvided(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => false]);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('');

        $this->expectException(ResourceStateException::class);
        $this->expectExceptionMessage('You must specify the database name for a private database server');

        $definition = new DatabaseDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgumentOnPublicServer(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $database = new Database('my_db', $databaseServer);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('my_db');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([$database]));

        $definition = new DatabaseDefinition();

        $this->assertSame($database, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoiceOnPublicServer(): void
    {
        $databaseServer = DatabaseServerFactory::create(['publicly_accessible' => true]);
        $database = new Database('choice_db', $databaseServer);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('database')->andReturn('');
        $this->apiClient->shouldReceive('getDatabases')->andReturn(new ResourceCollection([$database]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice_db');

        $definition = new DatabaseDefinition();

        $this->assertSame($database, $definition->resolve($this->context, 'question'));
    }
}
