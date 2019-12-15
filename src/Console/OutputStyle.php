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
        $this->write("<info>{$message}</info>", $newline);
    }

    /**
     * Write the build step message.
     */
    public function writeStep(string $step, bool $newline = true)
    {
        $this->write("  > $step", $newline);
    }

    /**
     * Write out an warning message.
     */
    public function warn(string $message, bool $newline = true)
    {
        $this->write("<comment>Warning: {$message}</comment>", $newline);
    }
}
