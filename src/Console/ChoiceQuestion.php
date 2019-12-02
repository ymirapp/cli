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

use Symfony\Component\Console\Question\ChoiceQuestion as SymfonyChoiceQuestion;

class ChoiceQuestion extends SymfonyChoiceQuestion
{
    /**
     * {@inheritdoc}
     */
    protected function isAssoc($array)
    {
        return !isset($array[0]);
    }
}
