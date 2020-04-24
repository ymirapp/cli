<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Console;

use Symfony\Component\Console\Style\SymfonyStyle;
use Tightenco\Collect\Support\Collection;

class OutputStyle extends SymfonyStyle
{
    /**
     * Ask a question and return the answer as a slug.
     */
    public function askSlug(string $question, string $default = null): string
    {
        return (string) preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($this->ask($question, $default))));
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
     * Ask a choice question using a Collection.
     */
    public function choiceCollection(string $question, Collection $collection): int
    {
        return (int) $this->choice(
            $question,
            $collection->mapWithKeys(function (array $item) {
                return [$item['id'] => $item['name']];
            })->all()
        );
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
     * Write the build step message.
     */
    public function writeStep(string $step, bool $newline = true)
    {
        $this->write(sprintf('  > %s', $step), $newline);
    }

    /**
     * Write out a warning message.
     */
    public function warn(string $message, bool $newline = true)
    {
        $this->write(sprintf('<comment>%s</comment>', $message), $newline);
    }
}
