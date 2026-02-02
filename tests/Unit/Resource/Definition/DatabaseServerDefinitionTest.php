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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Requirement\DatabaseServerStorageRequirement;
use Ymir\Cli\Resource\Requirement\DatabaseServerTypeRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\PrivateDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionNetworkRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerDefinitionTest extends TestCase
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
        $this->context->shouldReceive('getTeam')->andReturn(TeamFactory::create());
    }

    public function testGetRequirements(): void
    {
        $definition = new DatabaseServerDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(5, $requirements);
        $this->assertInstanceOf(NameSlugRequirement::class, $requirements['name']);
        $this->assertInstanceOf(ResolveOrProvisionNetworkRequirement::class, $requirements['network']);
        $this->assertInstanceOf(DatabaseServerTypeRequirement::class, $requirements['type']);
        $this->assertInstanceOf(DatabaseServerStorageRequirement::class, $requirements['storage']);
        $this->assertInstanceOf(PrivateDatabaseServerRequirement::class, $requirements['private']);
    }

    public function testProvision(): void
    {
        $databaseServer = DatabaseServerFactory::create();
        $network = NetworkFactory::create();

        $this->apiClient->shouldReceive('createDatabaseServer')->once()
                  ->with($network, 'name', 'type', 50, true)
                  ->andReturn($databaseServer);

        $definition = new DatabaseServerDefinition();

        $this->assertSame($databaseServer, $definition->provision($this->apiClient, [
            'network' => $network,
            'name' => 'name',
            'type' => 'type',
            'storage' => 50,
            'private' => false,
        ]));
    }

    public function testResolveFiltersByRegion(): void
    {
        $serverUsEast1 = DatabaseServerFactory::create(['id' => 1, 'name' => 'east-server', 'region' => 'us-east-1']);
        $serverUsWest2 = DatabaseServerFactory::create(['id' => 2, 'name' => 'west-server', 'region' => 'us-west-2']);

        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('server')->andReturn(false);
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([$serverUsEast1, $serverUsWest2]));

        $this->output->shouldReceive('choiceWithResourceDetails')->with('question', \Mockery::on(function ($servers) {
            return 1 === $servers->count() && 'us-west-2' === $servers->first()->getRegion();
        }))->andReturn('west-server');

        $definition = new DatabaseServerDefinition();

        $this->assertSame($serverUsWest2, $definition->resolve($this->context, 'question', ['region' => 'us-west-2']));
    }

    public function testResolveThrowsExceptionIfDatabaseServerNotFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('server')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([DatabaseServerFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a database server with "non-existent" as the ID or name');

        $definition = new DatabaseServerDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNameCollision(): void
    {
        $server1 = DatabaseServerFactory::create(['id' => 1, 'name' => 'duplicate']);
        $server2 = DatabaseServerFactory::create(['id' => 2, 'name' => 'duplicate']);

        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('server')->andReturn('duplicate');
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([$server1, $server2]));

        $this->expectException(ResourceResolutionException::class);
        $this->expectExceptionMessage('Unable to select a database server because more than one database server has the name "duplicate"');

        $definition = new DatabaseServerDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDatabaseServersFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('server')->andReturn(false);
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no database servers, but you can create one with the "database:server:create" command');

        $definition = new DatabaseServerDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfServerIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('server')->andReturn(false);
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([DatabaseServerFactory::create()]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid database server ID or name');

        $definition = new DatabaseServerDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $databaseServer = DatabaseServerFactory::create(['name' => 'my-server']);

        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(true);
        $this->input->shouldReceive('getStringArgument')->with('server')->andReturn('my-server');
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([$databaseServer]));

        $definition = new DatabaseServerDefinition();

        $this->assertSame($databaseServer, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $databaseServer = DatabaseServerFactory::create(['name' => 'choice-server']);

        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('server')->andReturn(false);
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([$databaseServer]));
        $this->output->shouldReceive('choiceWithResourceDetails')->andReturn('choice-server');

        $definition = new DatabaseServerDefinition();

        $this->assertSame($databaseServer, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $databaseServer = DatabaseServerFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('server')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('server')->andReturn(true);
        $this->input->shouldReceive('getStringOption')->with('server', true)->andReturn('123');
        $this->apiClient->shouldReceive('getDatabaseServers')->andReturn(new ResourceCollection([$databaseServer]));

        $definition = new DatabaseServerDefinition();

        $this->assertSame($databaseServer, $definition->resolve($this->context, 'question'));
    }
}
