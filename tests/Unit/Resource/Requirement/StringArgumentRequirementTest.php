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
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\StringArgumentRequirement;
use Ymir\Cli\Tests\TestCase;

class StringArgumentRequirementTest extends TestCase
{
    public function testFulfillAsksQuestionIfArgumentDoesNotExist(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('arg')->andReturn(false);

        $output->shouldReceive('ask')->with('Question?', 'default', \Mockery::type('callable'))->andReturn('answer');

        $requirement = new StringArgumentRequirement('arg', 'Question?', 'default');

        $this->assertSame('answer', $requirement->fulfill($context));
    }

    public function testFulfillAsksQuestionIfArgumentIsEmpty(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('arg')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('arg')->andReturn('');

        $output->shouldReceive('ask')->with('Question?', 'default', \Mockery::type('callable'))->andReturn('answer');

        $requirement = new StringArgumentRequirement('arg', 'Question?', 'default');

        $this->assertSame('answer', $requirement->fulfill($context));
    }

    public function testFulfillReturnsArgumentFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('hasArgument')->with('arg')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('arg')->andReturn('value');

        $requirement = new StringArgumentRequirement('arg', 'Question?');

        $this->assertSame('value', $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfAnswerIsEmpty(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('You must enter a "arg" argument');

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('arg')->andReturn(false);

        $output->shouldReceive('ask')->andReturn('');

        $requirement = new StringArgumentRequirement('arg', 'Question?');

        $requirement->fulfill($context);
    }
}
