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

use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\NonInteractiveRequiredOptionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\DatabaseServerEngineRequirement;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerEngineRequirementTest extends TestCase
{
    public function testFulfillPromptsForEngineIfMissingFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('engine', true)->andReturn(null);
        $input->shouldReceive('isInteractive')->andReturn(true);
        $output->shouldReceive('choice')->with('Which database do you want to create?', DatabaseServer::getEngineLabels())->andReturn(DatabaseServer::ENGINE_POSTGRESQL);

        $requirement = new DatabaseServerEngineRequirement('Which database do you want to create?');

        $this->assertSame(DatabaseServer::ENGINE_POSTGRESQL, $requirement->fulfill($context));
    }

    public function testFulfillReturnsMysqlFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine', true)->andReturn(DatabaseServer::ENGINE_MYSQL);

        $requirement = new DatabaseServerEngineRequirement('Which database do you want to create?');

        $this->assertSame(DatabaseServer::ENGINE_MYSQL, $requirement->fulfill($context));
    }

    public function testFulfillReturnsPostgresqlFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine', true)->andReturn(DatabaseServer::ENGINE_POSTGRESQL);

        $requirement = new DatabaseServerEngineRequirement('Which database do you want to create?');

        $this->assertSame(DatabaseServer::ENGINE_POSTGRESQL, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfEngineIsInvalid(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The database server engine must be either "mysql" or "postgresql"');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine', true)->andReturn('invalid');

        $requirement = new DatabaseServerEngineRequirement('Which database do you want to create?');

        $requirement->fulfill($context);
    }

    public function testFulfillThrowsExceptionIfEngineMissingInNonInteractiveInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(NonInteractiveRequiredOptionException::class);
        $this->expectExceptionMessage('You must use the "--engine" option when running in non-interactive mode');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine', true)->andThrow(new NonInteractiveRequiredOptionException('engine'));

        $requirement = new DatabaseServerEngineRequirement('Which database do you want to create?');

        $requirement->fulfill($context);
    }
}
