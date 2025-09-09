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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Requirement\ResourceRequirement;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\TestCase;

class ResourceRequirementTest extends TestCase
{
    public function testFulfillCallsResolveOnDefinition(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ResolvableResourceDefinitionInterface::class);
        $resource = SecretFactory::create();

        $definition->shouldReceive('resolve')->once()
                   ->with($context, 'Question?')
                   ->andReturn($resource);

        $requirement = new ResourceRequirement($definition, 'Question?');

        $this->assertSame($resource, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfResolvedResourceIsInvalid(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $definition = \Mockery::mock(ResolvableResourceDefinitionInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Required resource definition must return a resource model instance');

        $definition->shouldReceive('resolve')->once()->andReturn(new \stdClass());

        $requirement = new ResourceRequirement($definition, 'Question?');

        $requirement->fulfill($context);
    }
}
