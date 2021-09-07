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

use Symfony\Component\Console\Style\SymfonyStyle;
use Tightenco\Collect\Support\Collection;

class ConsoleOutput extends SymfonyStyle
{
    /**
     * Ask a question and return the answer as a slug.
     */
    public function askSlug(string $question, string $default = null): string
    {
        return (string) preg_replace('/[^a-z0-9-_]+/i', '-', strtolower(trim($this->ask($question, $default))));
    }

    /**
     * {@inheritdoc}
     */
    public function choice($question, array $choices, $default = null)
    {
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
     * Write out an exception message.
     */
    public function exception(\Exception $exception)
    {
        $this->block($exception->getMessage(), null, 'fg=white;bg=red', '  ', true);
    }

    /**
     * Format the resource for display.
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
     * Write out an informational message.
     */
    public function info(string $message, bool $newline = true)
    {
        $this->write(sprintf('<info>%s</info>', $message), $newline);
    }

    /**
     * Write out an informational message with an accompanied warning about a delay.
     */
    public function infoWithDelayWarning(string $message, bool $newline = true)
    {
        $this->infoWithWarning($message, 'process takes several minutes to complete', $newline);
    }

    /**
     * Write out an informational message followed by a value and an optional comment.
     */
    public function infoWithValue(string $message, string $value, string $comment = '', bool $newline = true)
    {
        $format = '<info>%s:</info> %s';

        if (!empty($comment)) {
            $format .= ' (<comment>%s</comment>)';
        }

        $this->write(sprintf($format, $message, $value, $comment), $newline);
    }

    /**
     * Write out an informational message with an accompanied warning.
     */
    public function infoWithWarning(string $message, string $warning, bool $newline = true)
    {
        $this->write(sprintf('<info>%s</info> (<comment>%s</comment>)', $message, $warning), $newline);
    }

    /**
     * Write out a list of items.
     */
    public function list(iterable $items)
    {
        $this->newLine();

        foreach ($items as $item) {
            $this->writeln(sprintf('  * %s', (string) $item));
        }
    }

    /**
     * Ask a multiselect choice question.
     */
    public function multichoice($question, array $choices, $default = null): array
    {
        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        $question = new ChoiceQuestion($question, $choices, $default);
        $question->setMultiselect(true);

        return (array) $this->askQuestion($question);
    }

    /**
     * Write out a warning message.
     */
    public function warn(string $message, bool $newline = true)
    {
        $this->write(sprintf('<comment>%s</comment>', $message), $newline);
    }

    /**
     * Write the build step message.
     */
    public function writeStep(string $step, bool $newline = true)
    {
        $this->write(sprintf('  > %s', $step), $newline);
    }
}
