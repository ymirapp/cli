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
}
