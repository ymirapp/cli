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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Tests\TestCase;

class OutputTest extends TestCase
{
    private $input;
    private $output;
    private $symfonyOutput;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = \Mockery::mock(InputInterface::class);
        $this->symfonyOutput = \Mockery::mock(SymfonyOutputInterface::class);
        $this->symfonyOutput->shouldReceive('getVerbosity')->andReturn(SymfonyOutputInterface::VERBOSITY_NORMAL);
        $this->symfonyOutput->shouldReceive('getFormatter')->andReturn(new OutputFormatter());

        $this->output = new Output($this->input, $this->symfonyOutput);
    }

    public function testCommentWritesCommentMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<comment>foo</comment>', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->comment('foo');
    }

    public function testExceptionWritesBlock(): void
    {
        $this->symfonyOutput->shouldReceive('getVerbosity')->andReturn(SymfonyOutputInterface::VERBOSITY_NORMAL);
        $this->symfonyOutput->shouldReceive('isDecorated')->andReturn(false);
        $this->symfonyOutput->shouldReceive('writeln')->once()->with(\Mockery::on(function ($argument) {
            return is_array($argument) && str_contains($argument[0], 'foo');
        }), SymfonyOutputInterface::OUTPUT_NORMAL);
        $this->symfonyOutput->shouldReceive('write')->atLeast()->once();

        $this->output->exception(new \Exception('foo'));
    }

    public function testFormatBooleanReturnsYesNo(): void
    {
        $this->assertSame('yes', $this->output->formatBoolean(true));
        $this->assertSame('no', $this->output->formatBoolean(false));
    }

    public function testFormatStatusReturnsFormattedStatus(): void
    {
        $this->assertSame('<info>available</info>', $this->output->formatStatus('available'));
        $this->assertSame('<fg=red>failed</>', $this->output->formatStatus('failed'));
        $this->assertSame('<fg=red>deleting</>', $this->output->formatStatus('deleting'));
        $this->assertSame('<comment>pending</comment>', $this->output->formatStatus('pending'));
    }

    public function testImportantWritesImportantMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<fg=red>Important:</> foo', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->important('foo');
    }

    public function testInfoWithDelayWarningWritesMessages(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo</info> (<comment>process takes several minutes to complete</comment>)', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->infoWithDelayWarning('foo');
    }

    public function testInfoWithRedeployWarningWritesMessages(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo</info>', SymfonyOutputInterface::OUTPUT_NORMAL);
        $this->symfonyOutput->shouldReceive('write')->once()->with(\PHP_EOL);
        $this->symfonyOutput->shouldReceive('writeln')->once()->with(\Mockery::on(function ($argument) {
            return str_contains($argument, 'Warning:') && str_contains($argument, 'bar');
        }), SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->infoWithRedeployWarning('foo', 'bar');
    }

    public function testInfoWithValueAndCommentWritesMessages(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo:</info> bar (<comment>baz</comment>)', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->infoWithValue('foo', 'bar', 'baz');
    }

    public function testInfoWithValueWritesMessages(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo:</info> bar', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->infoWithValue('foo', 'bar');
    }

    public function testInfoWithWarningWritesMessages(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo</info> (<comment>bar</comment>)', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->infoWithWarning('foo', 'bar');
    }

    public function testInfoWritesInfoMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<info>foo</info>', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->info('foo');
    }

    public function testListWritesItems(): void
    {
        $this->symfonyOutput->shouldReceive('write')->once()->with(\PHP_EOL);
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('  * foo', SymfonyOutputInterface::OUTPUT_NORMAL);
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('  * bar', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->list(['foo', 'bar']);
    }

    public function testNewLineWritesNewLines(): void
    {
        $this->symfonyOutput->shouldReceive('write')->once()->with(\PHP_EOL);
        $this->output->newLine();

        $this->symfonyOutput->shouldReceive('write')->once()->with(\PHP_EOL.\PHP_EOL);
        $this->output->newLine(2);
    }

    public function testNoteWritesNoteMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<comment>Note:</comment> foo', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->note('foo');
    }

    public function testWarningWritesWarningMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('<comment>Warning:</comment> foo', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->warning('foo');
    }

    public function testWritelnProxiesToSymfonyOutput(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('foo', SymfonyOutputInterface::OUTPUT_NORMAL);
        $this->output->writeln('foo');
    }

    public function testWriteProxiesToSymfonyOutput(): void
    {
        $this->symfonyOutput->shouldReceive('write')->once()->with('foo', false, SymfonyOutputInterface::OUTPUT_NORMAL);
        $this->output->write('foo');
    }

    public function testWriteStepWritesStepMessage(): void
    {
        $this->symfonyOutput->shouldReceive('writeln')->once()->with('  > foo', SymfonyOutputInterface::OUTPUT_NORMAL);

        $this->output->writeStep('foo');
    }
}
