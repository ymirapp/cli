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
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\PrivateDatabaseServerRequirement;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\TestCase;

class PrivateDatabaseServerRequirementTest extends TestCase
{
    public function testFulfillReturnsFalseIfUserWantsPublicServer(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => true]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $output->shouldReceive('confirm')->with('Should the database server be publicly accessible?')->andReturn(true);

        $requirement = new PrivateDatabaseServerRequirement();

        $this->assertFalse($requirement->fulfill($context, ['network' => $network, 'type' => 'mysql']));
    }

    public function testFulfillReturnsFalseIfUserWantsPublicServerButDeniesNatGateway(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => false]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $output->shouldReceive('confirm')->with('Should the database server be publicly accessible?')->andReturn(false);
        $output->shouldReceive('confirm')->with('A private database server requires that Ymir add a NAT gateway (~$32/month) to your network. Would you like to proceed? <fg=default>(Answering "<comment>no</comment>" will make the database server publicly accessible.)</>')->andReturn(false);

        $requirement = new PrivateDatabaseServerRequirement();

        $this->assertFalse($requirement->fulfill($context, ['network' => $network, 'type' => 'mysql']));
    }

    public function testFulfillReturnsTrueIfPrivateOptionUsed(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(true);

        $requirement = new PrivateDatabaseServerRequirement();

        $this->assertTrue($requirement->fulfill($context, ['network' => $network, 'type' => 'mysql']));
    }

    public function testFulfillReturnsTrueIfServerlessAndHasNatGateway(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => true]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $requirement = new PrivateDatabaseServerRequirement();

        $this->assertTrue($requirement->fulfill($context, ['network' => $network, 'type' => DatabaseServer::AURORA_DATABASE_TYPE]));
    }

    public function testFulfillReturnsTrueIfUserWantsPrivateServerAndAcceptsNatGateway(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => false]);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $output->shouldReceive('confirm')->with('Should the database server be publicly accessible?')->andReturn(false);
        $output->shouldReceive('confirm')->with('A private database server requires that Ymir add a NAT gateway (~$32/month) to your network. Would you like to proceed? <fg=default>(Answering "<comment>no</comment>" will make the database server publicly accessible.)</>')->andReturn(true);

        $requirement = new PrivateDatabaseServerRequirement();

        $this->assertTrue($requirement->fulfill($context, ['network' => $network, 'type' => 'mysql']));
    }

    public function testFulfillThrowsExceptionIfNatGatewayConfirmationCancelled(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => false]);

        $this->expectException(CommandCancelledException::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(false);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $output->shouldReceive('confirm')->with('An Aurora serverless database cluster requires that Ymir add a NAT gateway (~$32/month) to your network. Would you like to proceed? <fg=default>(Answering "<comment>no</comment>" will cancel the command.)</>')->andReturn(false);

        $requirement = new PrivateDatabaseServerRequirement();

        $requirement->fulfill($context, ['network' => $network, 'type' => DatabaseServer::AURORA_DATABASE_TYPE]);
    }

    public function testFulfillThrowsExceptionIfNetworkRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"network" must be fulfilled before fulfilling the private database server requirement');

        $requirement = new PrivateDatabaseServerRequirement();

        $requirement->fulfill($context, []);
    }

    public function testFulfillThrowsExceptionIfPublicOptionUsedWithServerless(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $network = NetworkFactory::create();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You cannot use the "--public" option when creating a serverless database server');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getBooleanOption')->with('public')->andReturn(true);
        $input->shouldReceive('getBooleanOption')->with('private')->andReturn(false);

        $requirement = new PrivateDatabaseServerRequirement();

        $requirement->fulfill($context, ['network' => $network, 'type' => DatabaseServer::AURORA_DATABASE_TYPE]);
    }

    public function testFulfillThrowsExceptionIfTypeRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $network = NetworkFactory::create();

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"type" must be fulfilled before fulfilling the private database server requirement');

        $requirement = new PrivateDatabaseServerRequirement();

        $requirement->fulfill($context, ['network' => $network]);
    }
}
