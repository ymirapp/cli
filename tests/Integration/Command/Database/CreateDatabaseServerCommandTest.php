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

namespace Ymir\Cli\Tests\Integration\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Database\CreateDatabaseServerCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\NonInteractiveRequiredOptionException;
use Ymir\Cli\Resource\Definition\DatabaseServerDefinition;
use Ymir\Cli\Resource\Definition\NetworkDefinition;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateDatabaseServerCommandTest extends TestCase
{
    public function testCreateDatabaseServerInteractively(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);
        $server = DatabaseServerFactory::createMysql([
            'name' => 'interactive-server',
            'type' => 'db.t3.small',
            'storage' => 50,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('getCloudProviders')->with($team)->andReturn(new ResourceCollection([]));
        $this->apiClient->shouldReceive('getDatabaseServerTypes')->andReturn(collect(['db.t3.small' => 'db.t3.small']));
        $this->apiClient->shouldReceive('createDatabaseServer')
            ->once()
            ->andReturn($server);

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseServerCommand::NAME, [], [
            'interactive-server', // name
            '1', // network choice
            'mysql', // database choice
            'n', // serverless
            'db.t3.small', // type choice
            '50', // storage
            'n', // private
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('What is the name of the database server being created?', $display);
        $this->assertStringContainsString('Do you want to create an Aurora serverless database cluster?', $display);
        $this->assertStringContainsString('Database server created', $display);
    }

    public function testCreateDatabaseServerWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);
        $server = DatabaseServerFactory::createMysql([
            'name' => 'new-server',
            'type' => 'db.t3.micro',
            'storage' => 20,
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('getDatabaseServerTypes')->andReturn(collect(['db.t3.micro' => 'db.t3.micro']));
        $this->apiClient->shouldReceive('createDatabaseServer')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($network) {
                return $arg instanceof Network && $arg->getId() === $network->getId();
            }), DatabaseServer::ENGINE_MYSQL, 'new-server', 'db.t3.micro', true, 20)
            ->andReturn($server);

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseServerCommand::NAME, [
            'name' => 'new-server',
            '--engine' => DatabaseServer::ENGINE_MYSQL,
            '--network' => '1',
            '--type' => 'db.t3.micro',
            '--storage' => '20',
            '--public' => true,
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database server created', $display);
        $this->assertStringContainsString('new-server', $display);
        $this->assertStringContainsString('db.t3.micro', $display);
    }

    public function testCreatePostgresqlDatabaseServerRejectsMismatchedAuroraTypeOption(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage(sprintf('The type "%s" isn\'t a valid database type', DatabaseServer::AURORA_MYSQL_DATABASE_TYPE));

        $team = $this->setupActiveTeam();
        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $this->executeCommand(CreateDatabaseServerCommand::NAME, [
            'name' => 'new-server',
            '--engine' => DatabaseServer::ENGINE_POSTGRESQL,
            '--network' => '1',
            '--private' => true,
            '--type' => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
        ]);
    }

    public function testCreatePostgresqlDatabaseServerWithArgumentsAndOptions(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);
        $server = DatabaseServerFactory::createPostgresql([
            'name' => 'new-server',
            'type' => 'db.t3.micro',
            'storage' => 20,
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('getDatabaseServerTypes')->andReturn(collect([
            'db.t3.micro' => 'db.t3.micro',
            DatabaseServer::AURORA_MYSQL_DATABASE_TYPE => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
            DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
        ]));
        $this->apiClient->shouldReceive('createDatabaseServer')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($network) {
                return $arg instanceof Network && $arg->getId() === $network->getId();
            }), DatabaseServer::ENGINE_POSTGRESQL, 'new-server', 'db.t3.micro', true, 20)
            ->andReturn($server);

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseServerCommand::NAME, [
            'name' => 'new-server',
            '--engine' => DatabaseServer::ENGINE_POSTGRESQL,
            '--network' => '1',
            '--type' => 'db.t3.micro',
            '--storage' => '20',
            '--public' => true,
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database server created', $display);
        $this->assertStringContainsString('PostgreSQL', $display);
        $this->assertStringContainsString('new-server', $display);
        $this->assertStringContainsString('db.t3.micro', $display);
    }

    public function testCreateServerlessDatabaseServerRequiresEngineWhenNonInteractive(): void
    {
        $team = $this->setupActiveTeam();
        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);

        $this->expectException(NonInteractiveRequiredOptionException::class);
        $this->expectExceptionMessage('You must use the "--engine" option when running in non-interactive mode');

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = new CommandTester($this->application->find(CreateDatabaseServerCommand::NAME));
        $tester->execute([
            'name' => 'new-server',
            '--network' => '1',
            '--private' => true,
            '--serverless' => true,
        ], ['interactive' => false]);
    }

    public function testCreateServerlessPostgresqlDatabaseServerIgnoresTypeOption(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);
        $server = DatabaseServerFactory::createPostgresql([
            'name' => 'new-server',
            'type' => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
            'storage' => null,
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('createDatabaseServer')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($network) {
                return $arg instanceof Network && $arg->getId() === $network->getId();
            }), DatabaseServer::ENGINE_POSTGRESQL, 'new-server', DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, null, null)
            ->andReturn($server);

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseServerCommand::NAME, [
            'name' => 'new-server',
            '--engine' => DatabaseServer::ENGINE_POSTGRESQL,
            '--network' => '1',
            '--private' => true,
            '--serverless' => true,
            '--type' => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database server created', $display);
        $this->assertStringContainsString('PostgreSQL', $display);
        $this->assertStringContainsString(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, $display);
    }

    public function testCreateServerlessPostgresqlDatabaseServerWithAuroraTypeOption(): void
    {
        $team = $this->setupActiveTeam();

        $network = NetworkFactory::create(['id' => 1, 'name' => 'my-network']);
        $server = DatabaseServerFactory::createPostgresql([
            'name' => 'new-server',
            'type' => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
            'storage' => null,
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getNetworks')->with($team)->andReturn(new ResourceCollection([$network]));
        $this->apiClient->shouldReceive('createDatabaseServer')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($network) {
                return $arg instanceof Network && $arg->getId() === $network->getId();
            }), DatabaseServer::ENGINE_POSTGRESQL, 'new-server', DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, null, null)
            ->andReturn($server);

        $this->bootApplication([new CreateDatabaseServerCommand($this->apiClient, $this->createExecutionContextFactory([
            DatabaseServer::class => function () { return new DatabaseServerDefinition(); },
            Network::class => function () { return new NetworkDefinition(); },
        ]))]);

        $tester = $this->executeCommand(CreateDatabaseServerCommand::NAME, [
            'name' => 'new-server',
            '--engine' => DatabaseServer::ENGINE_POSTGRESQL,
            '--network' => '1',
            '--private' => true,
            '--type' => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Database server created', $display);
        $this->assertStringContainsString('PostgreSQL', $display);
        $this->assertStringContainsString(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, $display);
    }
}
