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
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Requirement\ProjectTypeRequirement;
use Ymir\Cli\Tests\TestCase;

class ProjectTypeRequirementTest extends TestCase
{
    public function testFulfillReturnsDetectedProjectType(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $projectType1 = \Mockery::mock(ProjectTypeInterface::class);
        $projectType2 = \Mockery::mock(ProjectTypeInterface::class);

        $context->shouldReceive('getProjectDirectory')->andReturn('directory');

        $projectType1->shouldReceive('matchesProject')->once()->with('directory')->andReturn(false);
        $projectType2->shouldReceive('matchesProject')->once()->with('directory')->andReturn(true);

        $requirement = new ProjectTypeRequirement([$projectType1, $projectType2], 'Question?');

        $this->assertSame($projectType2, $requirement->fulfill($context));
    }

    public function testFulfillReturnsSelectedProjectTypeIfNoneDetected(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);
        $projectType1 = \Mockery::mock(ProjectTypeInterface::class);
        $projectType2 = \Mockery::mock(ProjectTypeInterface::class);

        $context->shouldReceive('getProjectDirectory')->andReturn('directory');
        $context->shouldReceive('getOutput')->once()->andReturn($output);

        $projectType1->shouldReceive('matchesProject')->once()->with('directory')->andReturn(false);
        $projectType1->shouldReceive('getName')->andReturn('Type 1');
        $projectType2->shouldReceive('matchesProject')->once()->with('directory')->andReturn(false);
        $projectType2->shouldReceive('getName')->andReturn('Type 2');

        $output->shouldReceive('choice')->once()
               ->with('Question?', \Mockery::on(function ($choices) {
                   return ['Type 1', 'Type 2'] === $choices->all();
               }))
               ->andReturn('Type 2');

        $requirement = new ProjectTypeRequirement([$projectType1, $projectType2], 'Question?');

        $this->assertSame($projectType2, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfSelectedProjectTypeNotFound(): void
    {
        $this->expectException(RequirementFulfillmentException::class);
        $this->expectExceptionMessage('no project type found');

        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);
        $projectType1 = \Mockery::mock(ProjectTypeInterface::class);

        $context->shouldReceive('getProjectDirectory')->andReturn('directory');
        $context->shouldReceive('getOutput')->once()->andReturn($output);

        $projectType1->shouldReceive('matchesProject')->once()->with('directory')->andReturn(false);
        $projectType1->shouldReceive('getName')->andReturn('Type 1');

        $output->shouldReceive('choice')->once()->andReturn('Invalid');

        $requirement = new ProjectTypeRequirement([$projectType1], 'Question?');

        $requirement->fulfill($context);
    }
}
