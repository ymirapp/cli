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
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\ServerlessDatabaseServerRequirement;
use Ymir\Cli\Tests\TestCase;

class ServerlessDatabaseServerRequirementTest extends TestCase
{
    public function testFulfillReturnsFalseIfAuroraTypeDoesNotMatchEngine(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(DatabaseServer::AURORA_MYSQL_DATABASE_TYPE);

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');

        $this->assertFalse($requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_POSTGRESQL]));
    }

    public function testFulfillReturnsFalseIfTypeIsNotAurora(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn('db.t3.micro');

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');

        $this->assertFalse($requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]));
    }

    public function testFulfillReturnsTrueFromInteractiveConfirmation(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(null);
        $input->shouldReceive('isInteractive')->andReturn(true);
        $output->shouldReceive('confirm')->with('Create serverless?', false)->andReturn(true);

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');

        $this->assertTrue($requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]));
    }

    public function testFulfillReturnsTrueIfAuroraTypeMatchesEngine(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('type')->andReturn(DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE);

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');

        $this->assertTrue($requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_POSTGRESQL]));
    }

    public function testFulfillReturnsTrueIfServerlessOptionProvided(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('serverless')->andReturn(true);

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');

        $this->assertTrue($requirement->fulfill($context, ['engine' => DatabaseServer::ENGINE_MYSQL]));
    }

    public function testFulfillThrowsExceptionIfEngineRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"engine" must be fulfilled before fulfilling the serverless database server requirement');

        $requirement = new ServerlessDatabaseServerRequirement('Create serverless?');
        $requirement->fulfill($context);
    }
}
