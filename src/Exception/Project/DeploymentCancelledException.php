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

namespace Ymir\Cli\Exception\Project;

use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Exception\WarningExceptionInterface;

/**
 * Exception thrown when a deployment was cancelled before it finished.
 */
class DeploymentCancelledException extends RuntimeException implements WarningExceptionInterface
{
    /**
     * Constructor.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message, 130);
    }
}
