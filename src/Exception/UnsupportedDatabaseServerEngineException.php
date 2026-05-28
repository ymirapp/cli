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

class UnsupportedDatabaseServerEngineException extends LogicException
{
    /**
     * Constructor.
     */
    public function __construct(string $engine)
    {
        parent::__construct(sprintf('Unsupported database server engine "%s".', $engine));
    }
}
