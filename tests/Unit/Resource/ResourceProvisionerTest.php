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

namespace Ymir\Cli\Tests\Unit\Resource;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Requirement\RequirementInterface;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\TestCase;
use Ymir\Sdk\Exception\ClientException;

class ResourceProvisionerTest extends TestCase
{
    public function testProvisionFulfillsRequirementsAndCallsProvision(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class)->shouldIgnoreMissing();
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $requirement1 = \Mockery::mock(RequirementInterface::class);
        $requirement2 = \Mockery::mock(RequirementInterface::class);
        $resource = SecretFactory::create();

        $definition->shouldReceive('getRequirements')->once()
                   ->andReturn([
                       'req1' => $requirement1,
                       'req2' => $requirement2,
                   ]);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $requirement1->shouldReceive('fulfill')->once()
                     ->with($context, [])
                     ->andReturn('val1');

        $requirement2->shouldReceive('fulfill')->once()
                     ->with($context, ['req1' => 'val1'])
                     ->andReturn('val2');

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, ['req1' => 'val1', 'req2' => 'val2'])
                   ->andReturn($resource);

        $provisioner = new ResourceProvisioner();

        $this->assertSame($resource, $provisioner->provision($definition, $context));
    }

    public function testProvisionRetriesOnFailureIfUserConfirms(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $exception = \Mockery::mock(ClientException::class);
        $resource = SecretFactory::create();

        $definition->shouldReceive('getRequirements')->twice()->andReturn([]);
        $definition->shouldReceive('getResourceName')->once()->andReturn('resource');

        $context->shouldReceive('getApiClient')->twice()->andReturn($apiClient);
        $context->shouldReceive('getOutput')->once()->andReturn($output);
        $context->shouldReceive('getInput')->once()->andReturn($input);

        $input->shouldReceive('isInteractive')->once()->andReturn(true);

        $output->shouldReceive('newLine')->once();
        $output->shouldReceive('exception')->once()->with($exception);
        $output->shouldReceive('confirm')->once()->with('Failed to provision the resource. Do you want to retry?')->andReturn(true);

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, [])
                   ->andThrow($exception);

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, [])
                   ->andReturn($resource);

        $provisioner = new ResourceProvisioner();

        $this->assertSame($resource, $provisioner->provision($definition, $context));
    }

    public function testProvisionSkipsFulfilledRequirements(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class)->shouldIgnoreMissing();
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $requirement1 = \Mockery::mock(RequirementInterface::class);
        $requirement2 = \Mockery::mock(RequirementInterface::class);
        $resource = SecretFactory::create();

        $definition->shouldReceive('getRequirements')->once()
                   ->andReturn([
                       'req1' => $requirement1,
                       'req2' => $requirement2,
                   ]);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $requirement1->shouldReceive('fulfill')->never();

        $requirement2->shouldReceive('fulfill')->once()
                     ->with($context, ['req1' => 'val1'])
                     ->andReturn('val2');

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, ['req1' => 'val1', 'req2' => 'val2'])
                   ->andReturn($resource);

        $provisioner = new ResourceProvisioner();

        $this->assertSame($resource, $provisioner->provision($definition, $context, ['req1' => 'val1']));
    }

    public function testProvisionThrowsCommandCancelledExceptionIfInputNotInteractiveOnFailure(): void
    {
        $this->expectException(CommandCancelledException::class);

        $apiClient = \Mockery::mock(ApiClient::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $exception = \Mockery::mock(ClientException::class);

        $definition->shouldReceive('getRequirements')->once()->andReturn([]);

        $context->shouldReceive('getApiClient')->once()->andReturn($apiClient);
        $context->shouldReceive('getOutput')->once()->andReturn($output);
        $context->shouldReceive('getInput')->once()->andReturn($input);

        $input->shouldReceive('isInteractive')->once()->andReturn(false);

        $output->shouldReceive('newLine')->once();
        $output->shouldReceive('exception')->once()->with($exception);

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, [])
                   ->andThrow($exception);

        $provisioner = new ResourceProvisioner();
        $provisioner->provision($definition, $context);
    }

    public function testProvisionThrowsCommandCancelledExceptionIfUserCancelsOnFailure(): void
    {
        $this->expectException(CommandCancelledException::class);

        $apiClient = \Mockery::mock(ApiClient::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $exception = \Mockery::mock(ClientException::class);

        $definition->shouldReceive('getRequirements')->once()->andReturn([]);
        $definition->shouldReceive('getResourceName')->once()->andReturn('resource');

        $context->shouldReceive('getApiClient')->once()->andReturn($apiClient);
        $context->shouldReceive('getOutput')->once()->andReturn($output);
        $context->shouldReceive('getInput')->once()->andReturn($input);

        $input->shouldReceive('isInteractive')->once()->andReturn(true);

        $output->shouldReceive('newLine')->once();
        $output->shouldReceive('exception')->once()->with($exception);
        $output->shouldReceive('confirm')->once()->with('Failed to provision the resource. Do you want to retry?')->andReturn(false);

        $definition->shouldReceive('provision')->once()
                   ->with($apiClient, [])
                   ->andThrow($exception);

        $provisioner = new ResourceProvisioner();
        $provisioner->provision($definition, $context);
    }
}
