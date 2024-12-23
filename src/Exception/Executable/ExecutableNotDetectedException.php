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

namespace Ymir\Cli\Exception\Executable;

use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Executable\ExecutableInterface;

class ExecutableNotDetectedException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(ExecutableInterface $executable)
    {
        parent::__construct(sprintf('Cannot detect %1$s on this computer. Please ensure %1$s is installed and properly configured.', $executable->getDisplayName()));
    }
}
