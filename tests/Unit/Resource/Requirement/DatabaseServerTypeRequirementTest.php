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
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\DatabaseServerTypeRequirement;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerTypeRequirementTest extends TestCase
{
    public function testFulfillFiltersAuroraTypeChoices(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $apiClient->shouldReceive('getDatabaseServerTypes')->with($network->getProvider())->andReturn(new Collection([
            'db.t3.micro' => 'db.t3.micro',
            DatabaseServer::AURORA_MYSQL_DATABASE_TYPE => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
            DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
        ]));

        $output->shouldReceive('choice')->with('Which type?', \Mockery::on(function (Collection $choices): bool {
            return ['db.t3.micro' => 'db.t3.micro'] === $choices->all();
        }), null)->andReturn('db.t3.micro');

        $requirement = new DatabaseServerTypeRequirement('Which type?');

        $this->assertSame('db.t3.micro', $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL, 'network' => $network]));
    }

    public function testFulfillReturnsAuroraPostgresqlTypeIfServerlessOptionProvided(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(true);

        $requirement = new DatabaseServerTypeRequirement('Which type?');

        $this->assertSame(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_POSTGRESQL]));
    }

    public function testFulfillReturnsAuroraTypeFromOptionIfServerlessOptionExists(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE);
        $input->shouldReceive('hasOption')->with('serverless')->andReturn(true);

        $requirement = new DatabaseServerTypeRequirement('Which type?');

        $this->assertSame(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE, $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_POSTGRESQL]));
    }

    public function testFulfillReturnsAuroraTypeIfServerlessOptionProvided(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(true);

        $requirement = new DatabaseServerTypeRequirement('Which type?');

        $this->assertSame(DatabaseServer::AURORA_MYSQL_DATABASE_TYPE, $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]));
    }

    public function testFulfillReturnsTypeFromChoice(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create();
        $types = new Collection(['db.t3.micro' => 'db.t3.micro']);

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $apiClient->shouldReceive('getDatabaseServerTypes')->with($network->getProvider())->andReturn($types);

        $output->shouldReceive('choice')->with('Which type?', \Mockery::on(function (Collection $choices): bool {
            return ['db.t3.micro' => 'db.t3.micro'] === $choices->all();
        }), 'db.t3.small')->andReturn('db.t3.micro');

        $requirement = new DatabaseServerTypeRequirement('Which type?', 'db.t3.small');

        $this->assertSame('db.t3.micro', $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL, 'network' => $network]));
    }

    public function testFulfillReturnsTypeFromOption(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn('db.t3.micro');

        $apiClient->shouldReceive('getDatabaseServerTypes')->with($network->getProvider())->andReturn(new Collection(['db.t3.micro' => 'db.t3.micro']));

        $requirement = new DatabaseServerTypeRequirement('Which type?');

        $this->assertSame('db.t3.micro', $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL, 'network' => $network]));
    }

    public function testFulfillThrowsExceptionIfAuroraTypeIsIncompatibleWithEngine(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage(sprintf('The type "%s" isn\'t a valid database type', DatabaseServer::AURORA_MYSQL_DATABASE_TYPE));

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(DatabaseServer::AURORA_MYSQL_DATABASE_TYPE);
        $input->shouldReceive('hasOption')->with('serverless')->andReturn(true);

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_POSTGRESQL]);
    }

    public function testFulfillThrowsExceptionIfAuroraTypeProvidedWithoutServerlessOption(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage(sprintf('The type "%s" isn\'t a valid database type', DatabaseServer::AURORA_MYSQL_DATABASE_TYPE));

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(DatabaseServer::AURORA_MYSQL_DATABASE_TYPE);
        $input->shouldReceive('hasOption')->with('serverless')->andReturn(false);

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]);
    }

    public function testFulfillThrowsExceptionIfEngineRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"engine" must be fulfilled before fulfilling the database server type requirement');

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, []);
    }

    public function testFulfillThrowsExceptionIfInvalidTypeProvided(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The type "invalid" isn\'t a valid database type');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn('invalid');

        $apiClient->shouldReceive('getDatabaseServerTypes')->with($network->getProvider())->andReturn(new Collection(['db.t3.micro' => 'db.t3.micro']));

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL, 'network' => $network]);
    }

    public function testFulfillThrowsExceptionIfNetworkRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"network" must be fulfilled before fulfilling the database server type requirement');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]);
    }

    public function testFulfillThrowsExceptionIfNoDatabaseServerTypesFound(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $this->expectException(RequirementFulfillmentException::class);
        $this->expectExceptionMessage('no database server types found');

        $context->shouldReceive('getApiClient')->andReturn($apiClient);
        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);

        $apiClient->shouldReceive('getDatabaseServerTypes')->with($network->getProvider())->andReturn(new Collection());

        $requirement = new DatabaseServerTypeRequirement('Which type?');
        $requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL, 'network' => $network]);
    }
}
