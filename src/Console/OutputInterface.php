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

use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;
use Tightenco\Collect\Support\Collection;

interface OutputInterface extends SymfonyOutputInterface
{
    /**
     * Asks a question.
     */
    public function ask(string $question, string $default = null, callable $validator = null);

    /**
     * Asks a question with the user input hidden.
     */
    public function askHidden(string $question);

    /**
     * Ask a question and return the answer as a slug.
     */
    public function askSlug(string $question, string $default = null): string;

    /**
     * Asks a choice question.
     */
    public function choice(string $question, $choices, $default = null);

    /**
     * Ask a choice question that uses the ID for answers.
     */
    public function choiceWithId(string $question, Collection $collection): int;

    /**
     * Ask a choice question with the resource details.
     */
    public function choiceWithResourceDetails(string $question, Collection $collection): string;

    /**
     * Write out a comment message.
     */
    public function comment(string $message);

    /**
     * Asks for confirmation.
     */
    public function confirm(string $question, bool $default = true): bool;

    /**
     * Write out an exception message.
     */
    public function exception(\Exception $exception);

    /**
     * Format a boolean value.
     */
    public function formatBoolean(bool $bool): string;

    /**
     * Format the status of a resource for display.
     */
    public function formatStatus(string $status): string;

    /**
     * Formats a horizontal table.
     */
    public function horizontalTable(array $headers, array $rows);

    /**
     * Write out an important message.
     */
    public function important(string $message);

    /**
     * Write out an informational message.
     */
    public function info(string $message);

    /**
     * Write out an informational message with an accompanied warning about a delay.
     */
    public function infoWithDelayWarning(string $message);

    /**
     * Write out an informational message with an accompanied warning about having to redeploy an environment.
     */
    public function infoWithRedeployWarning(string $message, string $environment);

    /**
     * Write out an informational message followed by a value and an optional comment.
     */
    public function infoWithValue(string $message, string $value, string $comment = '');

    /**
     * Write out an informational message with an accompanied warning.
     */
    public function infoWithWarning(string $message, string $warning);

    /**
     * Write out a list of items.
     */
    public function list(iterable $items);

    /**
     * Ask a multiselect choice question.
     */
    public function multichoice($question, $choices, $default = null): array;

    /**
     * Add newline(s).
     */
    public function newLine(int $count = 1);

    /**
     * Write out a note message.
     */
    public function note(string $message);

    /**
     * Formats a table.
     */
    public function table(array $headers, array $rows);

    /**
     * Write out a warning message.
     */
    public function warning(string $message);

    /**
     * Write the build step message.
     */
    public function writeStep(string $step);
}
