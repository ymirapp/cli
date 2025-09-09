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

namespace Ymir\Cli\Exception\Resource;

use Ymir\Cli\Exception\RuntimeException;

class RequirementFulfillmentException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(string $reason)
    {
        parent::__construct(sprintf('Unable to fulfill the requirement: %s', lcfirst($reason)));
    }
}
