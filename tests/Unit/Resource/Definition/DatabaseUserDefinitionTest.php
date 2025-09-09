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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\DatabaseUserDefinition;
use Ymir\Cli\Resource\Requirement\DatabasesRequirement;
use Ymir\Cli\Resource\Requirement\ParentDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\StringArgumentRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\DatabaseUserFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseUserDefinitionTest extends TestCase
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
        $definition = new DatabaseUserDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(3, $requirements);
        $this->assertInstanceOf(ParentDatabaseServerRequirement::class, $requirements['server']);
        $this->assertInstanceOf(StringArgumentRequirement::class, $requirements['user']);
        $this->assertInstanceOf(DatabasesRequirement::class, $requirements['databases']);
    }

    public function testProvision(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $databaseUser = DatabaseUserFactory::create();

        $this->apiClient->shouldReceive('createDatabaseUser')->once()
                  ->with($databaseServer, 'user', ['db1'])
                  ->andReturn($databaseUser);

        $definition = new DatabaseUserDefinition();

        $this->assertSame($databaseUser, $definition->provision($this->apiClient, [
            'server' => $databaseServer,
            'user' => 'user',
            'databases' => ['db1'],
        ]));
    }

    public function testResolveThrowsExceptionIfDatabaseUserNotFound(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('user')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getDatabaseUsers')->andReturn(new ResourceCollection([DatabaseUserFactory::create(['username' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a database user with "non-existent" as the ID or name');

        $definition = new DatabaseUserDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDatabaseServerInContext(): void
    {
        $this->context->shouldReceive('getParentResource')->andReturn(null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A DatabaseServer must be resolved and passed into the context before resolving a database user');

        $definition = new DatabaseUserDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDatabaseUsersFound(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->apiClient->shouldReceive('getDatabaseUsers')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage(sprintf('The "%s" database server doesn\'t have any managed database users', $databaseServer->getName()));

        $definition = new DatabaseUserDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfUsernameIsEmptyAfterChoice(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->apiClient->shouldReceive('getDatabaseUsers')->andReturn(new ResourceCollection([DatabaseUserFactory::create()]));
        $this->input->shouldReceive('getStringArgument')->with('user')->andReturn('');
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid database server username');

        $definition = new DatabaseUserDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $databaseUser = DatabaseUserFactory::create(['username' => 'my-user']);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('user')->andReturn('my-user');
        $this->apiClient->shouldReceive('getDatabaseUsers')->andReturn(new ResourceCollection([$databaseUser]));

        $definition = new DatabaseUserDefinition();

        $this->assertSame($databaseUser, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $databaseUser = DatabaseUserFactory::create(['username' => 'choice-user']);
        $this->context->shouldReceive('getParentResource')->andReturn($databaseServer);
        $this->input->shouldReceive('getStringArgument')->with('user')->andReturn('');
        $this->apiClient->shouldReceive('getDatabaseUsers')->andReturn(new ResourceCollection([$databaseUser]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice-user');

        $definition = new DatabaseUserDefinition();

        $this->assertSame($databaseUser, $definition->resolve($this->context, 'question'));
    }
}
