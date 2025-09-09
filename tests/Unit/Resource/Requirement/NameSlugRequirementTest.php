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
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Tests\TestCase;

class NameSlugRequirementTest extends TestCase
{
    public function testFulfillAsksQuestionIfNameArgumentDoesNotExist(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('name')->andReturn(false);

        $output->shouldReceive('askSlug')->with('Question?', 'default', \Mockery::type('callable'))->andReturn('answer');

        $requirement = new NameSlugRequirement('Question?', 'default');

        $this->assertSame('answer', $requirement->fulfill($context));
    }

    public function testFulfillReturnsNameArgumentFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);

        $input->shouldReceive('hasArgument')->with('name')->andReturn(true);
        $input->shouldReceive('getStringArgument')->with('name')->andReturn('value');

        $requirement = new NameSlugRequirement('Question?');

        $this->assertSame('value', $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfAnswerIsEmpty(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('You must enter a "name" argument');

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('hasArgument')->with('name')->andReturn(false);

        $output->shouldReceive('askSlug')->andReturn('');

        $requirement = new NameSlugRequirement('Question?');

        $requirement->fulfill($context);
    }
}
