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

use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionResourceRequirement;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Tests\TestCase;

class ResolveOrProvisionResourceRequirementTest extends TestCase
{
    public function testConstructorThrowsExceptionIfDefinitionIsNotResolvable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Resource definition must implement ResolvableResourceDefinitionInterface');

        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);

        new ResolveOrProvisionResourceRequirement($definition, 'Question?');
    }

    public function testFulfillProvisionsResourceIfNoneFound(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class, ResolvableResourceDefinitionInterface::class);
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $resource = \Mockery::mock(ResourceModelInterface::class);

        $context->shouldReceive('getProvisioner')->andReturn($provisioner);

        $definition->shouldReceive('resolve')->once()->with($context, 'Question?', [])->andThrow(new NoResourcesFoundException('No resources found'));

        $provisioner->shouldReceive('provision')->once()
                    ->with($definition, $context, ['pre' => 'filled'])
                    ->andReturn($resource);

        $requirement = new ResolveOrProvisionResourceRequirement($definition, 'Question?', ['pre' => 'filled']);

        $this->assertSame($resource, $requirement->fulfill($context));
    }

    public function testFulfillProvisionsResourceWithMergedRequirements(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class, ResolvableResourceDefinitionInterface::class);
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $resource = \Mockery::mock(ResourceModelInterface::class);

        $context->shouldReceive('getProvisioner')->andReturn($provisioner);

        $definition->shouldReceive('resolve')->once()->andThrow(new NoResourcesFoundException('No resources found'));

        $provisioner->shouldReceive('provision')->once()
                    ->with($definition, $context, ['fulfilled' => 'requirement', 'pre' => 'filled'])
                    ->andReturn($resource);

        $requirement = new ResolveOrProvisionResourceRequirement($definition, 'Question?', ['pre' => 'filled']);

        $this->assertSame($resource, $requirement->fulfill($context, ['fulfilled' => 'requirement']));
    }

    public function testFulfillReturnsResolvedResource(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class, ResolvableResourceDefinitionInterface::class);
        $resource = \Mockery::mock(ResourceModelInterface::class);

        $definition->shouldReceive('resolve')->once()->with($context, 'Question?', [])->andReturn($resource);

        $requirement = new ResolveOrProvisionResourceRequirement($definition, 'Question?');

        $this->assertSame($resource, $requirement->fulfill($context));
    }
}
