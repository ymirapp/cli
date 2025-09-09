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

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\ProjectRequirement;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class ProjectRequirementTest extends TestCase
{
    public function testFulfillReturnsParentResourceIfItIsAProject(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getParentResource')->andReturn($project);

        $requirement = new ProjectRequirement();

        $this->assertSame($project, $requirement->fulfill($context));
    }

    public function testFulfillReturnsProjectFromContext(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getParentResource')->andReturn(null);
        $context->shouldReceive('getProject')->andReturn($project);

        $requirement = new ProjectRequirement();

        $this->assertSame($project, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfNoProjectInContext(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A project must be resolved and existing in the context before fulfilling this requirement');

        $context->shouldReceive('getParentResource')->andReturn(null);
        $context->shouldReceive('getProject')->andReturn(null);

        $requirement = new ProjectRequirement();

        $requirement->fulfill($context);
    }
}
