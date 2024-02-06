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

namespace Ymir\Cli\Console;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\TrimmedBufferOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Terminal;
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;

class Output implements OutputInterface
{
    /**
     * Maximum allowed line height.
     *
     * @var int
     */
    private const MAX_LINE_LENGTH = 120;

    /**
     * Buffered output used to generate tables.
     *
     * @var TrimmedBufferOutput
     */
    private $bufferedOutput;

    /**
     * Symfony console input.
     *
     * @var InputInterface
     */
    private $input;

    /**
     * Calculated line height.
     *
     * @var int
     */
    private $lineLength;

    /**
     * Symfony console output.
     *
     * @var OutputInterface
     */
    private $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->bufferedOutput = new TrimmedBufferOutput(\DIRECTORY_SEPARATOR === '\\' ? 4 : 2, $output->getVerbosity(), false, clone $output->getFormatter());
        $this->input = $input;
        $this->output = $output;

        $width = (new Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = min($width - (int) (\DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
    }

    /**
     * Asks a question.
     */
    public function ask(string $question, ?string $default = null, ?callable $validator = null)
    {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestion($question);
    }

    /**
     * Asks a question with the user input hidden.
     */
    public function askHidden(string $question)
    {
        $question = new Question($question);

        $question->setHidden(true);

        return $this->askQuestion($question);
    }

    /**
     * Asks a choice question.
     */
    public function askSlug(string $question, ?string $default = null): string
    {
        return (string) preg_replace('/[^a-z0-9-_]+/i', '-', strtolower(trim($this->ask($question, $default))));
    }

    /**
     * Ask a choice question that uses the ID for answers.
     */
    public function choice($question, $choices, $default = null)
    {
        $choices = $this->getChoices($choices);

        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        return $this->askQuestion(new ChoiceQuestion($question, $choices, $default));
    }

    /**
     * Ask a choice question that uses the ID for answers.
     */
    public function choiceWithId(string $question, Collection $collection): int
    {
        return (int) $this->choice(
            $question,
            $collection->mapWithKeys(function (array $item) {
                return [$item['id'] => $item['name']];
            })->all()
        );
    }

    /**
     * Ask a choice question with the resource details.
     */
    public function choiceWithResourceDetails(string $question, Collection $collection): string
    {
        return (string) preg_replace('/^([^ ]*) .*/', '$1', (string) $this->choice($question, $collection->map(function (array $resource) {
            return sprintf('%s (%s) [%s]', $resource['name'], $resource['region'], $this->formatStatus($resource['status']));
        })->all()));
    }

    /**
     * Write out a comment message.
     */
    public function comment(string $message)
    {
        $this->writeln(sprintf('<comment>%s</comment>', $message));
    }

    /**
     * Asks for confirmation.
     */
    public function confirm(string $question, bool $default = true): bool
    {
        return (bool) $this->askQuestion(new ConfirmationQuestion($question, $default));
    }

    /**
     * Write out an exception message.
     */
    public function exception(\Exception $exception)
    {
        $this->block($exception->getMessage(), null, 'fg=white;bg=red', '  ', true);
    }

    /**
     * Format a boolean value.
     */
    public function formatBoolean(bool $bool): string
    {
        return $bool ? 'yes' : 'no';
    }

    /**
     * Format the status of a resource for display.
     */
    public function formatStatus(string $status): string
    {
        $format = '<comment>%s</comment>';

        if (in_array($status, ['deleting', 'failed'])) {
            $format = '<fg=red>%s</>';
        } elseif ('available' === $status) {
            $format = '<info>%s</info>';
        }

        return sprintf($format, str_replace('_', ' ', $status));
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    /**
     * Formats a horizontal table.
     */
    public function horizontalTable(array $headers, array $rows)
    {
        $this->createTable()
             ->setHorizontal(true)
             ->setHeaders($headers)
             ->setRows($rows)
             ->render()
        ;

        $this->newLine();
    }

    /**
     * Write out an important message.
     */
    public function important(string $message)
    {
        $this->writeln(sprintf('<fg=red>Important:</> %s', $message));
    }

    /**
     * Write out an informational message.
     */
    public function info(string $message)
    {
        $this->writeln(sprintf('<info>%s</info>', $message));
    }

    /**
     * Write out an informational message with an accompanied warning about a delay.
     */
    public function infoWithDelayWarning(string $message)
    {
        $this->infoWithWarning($message, 'process takes several minutes to complete');
    }

    /**
     * Write out an informational message with an accompanied warning about having to redeploy an environment.
     */
    public function infoWithRedeployWarning(string $message, string $environment)
    {
        $this->info($message);
        $this->newLine();
        $this->warning(sprintf('You need to redeploy the project to the "<comment>%s</comment>" environment using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', $environment, DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }

    /**
     * Write out an informational message followed by a value and an optional comment.
     */
    public function infoWithValue(string $message, string $value, string $comment = '')
    {
        $format = '<info>%s:</info> %s';

        if (!empty($comment)) {
            $format .= ' (<comment>%s</comment>)';
        }

        $this->writeln(sprintf($format, $message, $value, $comment));
    }

    /**
     * Write out an informational message with an accompanied warning.
     */
    public function infoWithWarning(string $message, string $warning)
    {
        $this->writeln(sprintf('<info>%s</info> (<comment>%s</comment>)', $message, $warning));
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Write out a list of items.
     */
    public function list(iterable $items)
    {
        $this->newLine();

        foreach ($items as $item) {
            $this->writeln(sprintf('  * %s', $item));
        }
    }

    /**
     * Ask a multiselect choice question.
     */
    public function multichoice($question, $choices, $default = null): array
    {
        $choices = $this->getChoices($choices);

        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        $question = new ChoiceQuestion($question, $choices, $default);
        $question->setMultiselect(true);

        return (array) $this->askQuestion($question);
    }

    /**
     * Add newline(s).
     */
    public function newLine(int $count = 1)
    {
        $this->output->write(str_repeat(\PHP_EOL, $count));
    }

    /**
     * Write out a note message.
     */
    public function note(string $message)
    {
        $this->writeln(sprintf('<comment>Note:</comment> %s', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated(bool $decorated)
    {
        $this->output->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->output->setFormatter($formatter);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity(int $level)
    {
        $this->output->setVerbosity($level);
    }

    /**
     * Formats a table.
     */
    public function table(array $headers, array $rows)
    {
        $this->createTable()
             ->setHeaders($headers)
             ->setRows($rows)
             ->render()
        ;

        $this->newLine();
    }

    /**
     * Write out a warning message.
     */
    public function warning(string $message)
    {
        $this->writeln(sprintf('<comment>Warning:</comment> %s', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, bool $newline = false, int $type = self::OUTPUT_NORMAL)
    {
        $this->output->write($messages, $newline, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, int $type = self::OUTPUT_NORMAL)
    {
        $this->output->writeln($messages, $type);
    }

    /**
     * Write the build step message.
     */
    public function writeStep(string $step)
    {
        $this->writeln(sprintf('  > %s', $step));
    }

    /**
     * Ask a question.
     */
    private function askQuestion(Question $question)
    {
        if ($this->input->isInteractive()) {
            $this->autoPrependBlock();
        }

        $answer = (new SymfonyQuestionHelper())->ask($this->input, $this, $question);

        if ($this->input->isInteractive()) {
            $this->newLine();
            $this->bufferedOutput->write("\n");
        }

        return $answer;
    }

    private function autoPrependBlock(): void
    {
        $chars = substr(str_replace(\PHP_EOL, "\n", $this->bufferedOutput->fetch()), -2);

        if (!isset($chars[0])) {
            $this->newLine(); // empty history, so we should start with a new line.

            return;
        }

        // Prepend new line for each non LF chars (This means no blank line was output before)
        $this->newLine(2 - substr_count($chars, "\n"));
    }

    /**
     * Formats a message as a block of text.
     */
    private function block($messages, ?string $type = null, ?string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = true)
    {
        $messages = \is_array($messages) ? array_values($messages) : [$messages];

        $this->autoPrependBlock();
        $this->writeln($this->createBlock($messages, $type, $style, $prefix, $padding, $escape));
        $this->newLine();
    }

    /**
     * Create a block.
     */
    private function createBlock(array $messages, ?string $type = null, ?string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = false): array
    {
        $indentLength = 0;
        $prefixLength = Helper::width(Helper::removeDecoration($this->output->getFormatter(), $prefix));
        $lineIndentation = '';
        $lines = [];

        if (null !== $type) {
            $type = sprintf('[%s] ', $type);
            $indentLength = \strlen($type);
            $lineIndentation = str_repeat(' ', $indentLength);
        }

        // wrap and add newlines for each element
        foreach ($messages as $key => $message) {
            if ($escape) {
                $message = OutputFormatter::escape($message);
            }

            $decorationLength = Helper::width($message) - Helper::width(Helper::removeDecoration($this->output->getFormatter(), $message));
            $messageLineLength = min($this->lineLength - $prefixLength - $indentLength + $decorationLength, $this->lineLength);
            $messageLines = explode(\PHP_EOL, wordwrap($message, $messageLineLength, \PHP_EOL, true));
            foreach ($messageLines as $messageLine) {
                $lines[] = $messageLine;
            }

            if (\count($messages) > 1 && $key < \count($messages) - 1) {
                $lines[] = '';
            }
        }

        $firstLineIndex = 0;
        if ($padding && $this->output->isDecorated()) {
            $firstLineIndex = 1;
            array_unshift($lines, '');
            $lines[] = '';
        }

        foreach ($lines as $i => &$line) {
            if (null !== $type) {
                $line = $firstLineIndex === $i ? $type.$line : $lineIndentation.$line;
            }

            $line = $prefix.$line;
            $line .= str_repeat(' ', max($this->lineLength - Helper::width(Helper::removeDecoration($this->output->getFormatter(), $line)), 0));

            if ($style) {
                $line = sprintf('<%s>%s</>', $style, $line);
            }
        }

        return $lines;
    }

    /**
     * Create a table.
     */
    private function createTable(): Table
    {
        $output = $this->output instanceof ConsoleOutputInterface ? $this->output->section() : $this->output;
        $style = clone Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');

        return (new Table($output))->setStyle($style);
    }

    /**
     * Get the choices as an array.
     */
    private function getChoices($choices): array
    {
        if ($choices instanceof Enumerable) {
            $choices = $choices->all();
        } elseif (!is_array($choices)) {
            throw new InvalidArgumentException('"choices" must be an array or enumerable object');
        }

        return $choices;
    }
}
