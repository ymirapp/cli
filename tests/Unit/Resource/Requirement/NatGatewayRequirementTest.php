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

use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\NatGatewayRequirement;
use Ymir\Cli\Tests\Factory\NetworkFactory;
use Ymir\Cli\Tests\TestCase;

class NatGatewayRequirementTest extends TestCase
{
    public function testFulfillReturnsTrueIfConfirmationIsAccepted(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => false]);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('confirm')->with('Question?')->andReturn(true);

        $requirement = new NatGatewayRequirement('Question?');

        $this->assertTrue($requirement->fulfill($context, ['network' => $network]));
    }

    public function testFulfillReturnsTrueIfNetworkHasNatGateway(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $network = NetworkFactory::create(['has_nat_gateway' => true]);

        $requirement = new NatGatewayRequirement('Question?');

        $this->assertTrue($requirement->fulfill($context, ['network' => $network]));
    }

    public function testFulfillThrowsExceptionIfConfirmationIsCancelled(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);
        $network = NetworkFactory::create(['has_nat_gateway' => false]);

        $this->expectException(CommandCancelledException::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('confirm')->with('Question?')->andReturn(false);

        $requirement = new NatGatewayRequirement('Question?');

        $requirement->fulfill($context, ['network' => $network]);
    }

    public function testFulfillThrowsExceptionIfNetworkRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"network" must be fulfilled before fulfilling the nat gateway requirement');

        $requirement = new NatGatewayRequirement('Question?');

        $requirement->fulfill($context, []);
    }
}
