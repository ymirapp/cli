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
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\CacheClusterEngineRequirement;
use Ymir\Cli\Tests\TestCase;

class CacheClusterEngineRequirementTest extends TestCase
{
    public function testFulfillReturnsRedisFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine')->andReturn('redis');

        $requirement = new CacheClusterEngineRequirement();

        $this->assertSame('redis', $requirement->fulfill($context));
    }

    public function testFulfillReturnsValkeyFromInput(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine')->andReturn('valkey');

        $requirement = new CacheClusterEngineRequirement();

        $this->assertSame('valkey', $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfEngineIsInvalid(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The cache cluster engine must be either "redis" or "valkey"');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('engine')->andReturn('invalid');

        $requirement = new CacheClusterEngineRequirement();

        $requirement->fulfill($context);
    }
}
