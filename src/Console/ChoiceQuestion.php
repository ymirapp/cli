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
