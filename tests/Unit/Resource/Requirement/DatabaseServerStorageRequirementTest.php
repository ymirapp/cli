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
use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\DatabaseServerStorageRequirement;
use Ymir\Cli\Tests\TestCase;

class DatabaseServerStorageRequirementTest extends TestCase
{
    public function testConstructorThrowsExceptionIfDefaultIsNonNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default storage value must be a numeric value');

        new DatabaseServerStorageRequirement('Question?', 'invalid');
    }

    public function testFulfillReturnsNullIfTypeIsAurora(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $requirement = new DatabaseServerStorageRequirement('Question?');

        $this->assertNull($requirement->fulfill($context, ['type' => DatabaseServer::AURORA_DATABASE_TYPE]));
    }

    public function testFulfillReturnsStorageFromAskIfOptionNotProvided(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);

        $input->shouldReceive('getNumericOption')->with('storage')->andReturn(null);

        $output->shouldReceive('ask')->with('Question?', '50', \Mockery::type('callable'))->andReturn(50);

        $requirement = new DatabaseServerStorageRequirement('Question?', '50');

        $this->assertSame(50, $requirement->fulfill($context, ['type' => 'mysql']));
    }

    public function testFulfillReturnsStorageFromOptionIfProvided(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getNumericOption')->with('storage')->andReturn(100);

        $requirement = new DatabaseServerStorageRequirement('Question?');

        $this->assertSame(100, $requirement->fulfill($context, ['type' => 'mysql']));
    }

    public function testFulfillThrowsExceptionIfStorageIsNegative(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('The storage value must be a positive integer');

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getNumericOption')->with('storage')->andReturn(-10);

        $requirement = new DatabaseServerStorageRequirement('Question?');
        $requirement->fulfill($context, ['type' => 'mysql']);
    }

    public function testFulfillThrowsExceptionIfTypeRequirementMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"type" must be fulfilled before fulfilling the database server storage requirement');

        $requirement = new DatabaseServerStorageRequirement('Question?');

        $requirement->fulfill($context, []);
    }
}
