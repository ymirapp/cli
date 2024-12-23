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

class SshPortInUseException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(int $port)
    {
        parent::__construct(sprintf('Unable to open SSH tunnel. Local port "%s" is already in use.', $port));
    }
}
