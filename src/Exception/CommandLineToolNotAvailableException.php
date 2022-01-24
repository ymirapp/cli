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

namespace Ymir\Cli\Exception;

use Symfony\Component\Console\Exception\RuntimeException;

class CommandLineToolNotAvailableException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct($name)
    {
        parent::__construct(sprintf('%s isn\'t available', $name));
    }
}
