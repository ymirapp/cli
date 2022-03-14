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

use Symfony\Component\Console\Input\InputDefinition as SymfonyInputDefinition;
use Symfony\Component\Console\Input\InputOption;

class InputDefinition extends SymfonyInputDefinition
{
    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return array_filter(parent::getOptions(), function (InputOption $option) {
            return !$option instanceof HiddenInputOption;
        });
    }
}
