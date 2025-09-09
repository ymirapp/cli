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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\NameRequirement;
use Ymir\Cli\Tests\TestCase;

class NameRequirementTest extends TestCase
{
    public function testFulfillReturnsNameFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class)->shouldIgnoreMissing();
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')
                ->andReturn($input);

        $input->shouldReceive('hasArgument')->once()
              ->with('name')
              ->andReturn(true);

        $input->shouldReceive('getStringArgument')->once()
              ->with('name')
              ->andReturn('value');

        $requirement = new NameRequirement('Question?');

        $this->assertSame('value', $requirement->fulfill($context));
    }
}
