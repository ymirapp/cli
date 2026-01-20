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

namespace Ymir\Cli\Tests\Unit\Console;

use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\NonInteractiveRequiredArgumentException;
use Ymir\Cli\Exception\NonInteractiveRequiredOptionException;
use Ymir\Cli\Tests\TestCase;

class InputTest extends TestCase
{
    public function testGetArgumentReturnsValueFromSymfonyInput(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $this->assertSame('bar', $input->getArgument('foo'));
    }

    public function testGetArrayArgumentReturnsArray(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn(['bar']);

        $input = new Input($symfonyInput);

        $this->assertSame(['bar'], $input->getArrayArgument('foo'));
    }

    public function testGetArrayArgumentThrowsExceptionIfNotArray(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "foo" argument must be an array value');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $input->getArrayArgument('foo');
    }

    public function testGetArrayArgumentThrowsExceptionIfRequiredAndNonInteractive(): void
    {
        $this->expectException(NonInteractiveRequiredArgumentException::class);

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn(null);
        $symfonyInput->shouldReceive('isInteractive')->once()->andReturn(false);

        $input = new Input($symfonyInput);

        $input->getArrayArgument('foo');
    }

    public function testGetArrayOptionReturnsArray(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn(['bar']);

        $input = new Input($symfonyInput);

        $this->assertSame(['bar'], $input->getArrayOption('foo'));
    }

    public function testGetArrayOptionThrowsExceptionIfNotArray(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "--foo" option must be an array');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $input->getArrayOption('foo');
    }

    public function testGetArrayOptionThrowsExceptionIfRequiredAndNonInteractive(): void
    {
        $this->expectException(NonInteractiveRequiredOptionException::class);

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn(null);
        $symfonyInput->shouldReceive('isInteractive')->once()->andReturn(false);

        $input = new Input($symfonyInput);

        $input->getArrayOption('foo', true);
    }

    public function testGetBooleanOptionReturnsFalseIfMissing(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(false);

        $input = new Input($symfonyInput);

        $this->assertFalse($input->getBooleanOption('foo'));
    }

    public function testGetBooleanOptionReturnsTrue(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn(true);

        $input = new Input($symfonyInput);

        $this->assertTrue($input->getBooleanOption('foo'));
    }

    public function testGetNumericArgumentReturnsInt(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn('123');

        $input = new Input($symfonyInput);

        $this->assertSame(123, $input->getNumericArgument('foo'));
    }

    public function testGetNumericArgumentThrowsExceptionIfNotNumeric(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "foo" argument must be a numeric value');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $input->getNumericArgument('foo');
    }

    public function testGetNumericOptionReturnsInt(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn('123');

        $input = new Input($symfonyInput);

        $this->assertSame(123, $input->getNumericOption('foo'));
    }

    public function testGetNumericOptionThrowsExceptionIfNotNumeric(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "--foo" option must be a numeric value');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $input->getNumericOption('foo');
    }

    public function testGetOptionReturnsValueFromSymfonyInput(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $this->assertSame('bar', $input->getOption('foo'));
    }

    public function testGetStringArgumentReturnsString(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $this->assertSame('bar', $input->getStringArgument('foo'));
    }

    public function testGetStringArgumentThrowsExceptionIfNotString(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "foo" argument must be a string value');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('getArgument')->once()->with('foo')->andReturn(['bar']);

        $input = new Input($symfonyInput);

        $input->getStringArgument('foo');
    }

    public function testGetStringOptionReturnsString(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn('bar');

        $input = new Input($symfonyInput);

        $this->assertSame('bar', $input->getStringOption('foo'));
    }

    public function testGetStringOptionThrowsExceptionIfNotString(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "--foo" option must be a string value');

        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);
        $symfonyInput->shouldReceive('getOption')->once()->with('foo')->andReturn(['bar']);

        $input = new Input($symfonyInput);

        $input->getStringOption('foo');
    }

    public function testHasArgumentReturnsBoolean(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasArgument')->once()->with('foo')->andReturn(true);

        $input = new Input($symfonyInput);

        $this->assertTrue($input->hasArgument('foo'));
    }

    public function testHasOptionReturnsBoolean(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('hasOption')->once()->with('foo')->andReturn(true);

        $input = new Input($symfonyInput);

        $this->assertTrue($input->hasOption('foo'));
    }

    public function testIsInteractiveReturnsBoolean(): void
    {
        $symfonyInput = \Mockery::mock(SymfonyInputInterface::class);
        $symfonyInput->shouldReceive('isInteractive')->once()->andReturn(true);

        $input = new Input($symfonyInput);

        $this->assertTrue($input->isInteractive());
    }
}
