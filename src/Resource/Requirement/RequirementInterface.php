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

namespace Ymir\Cli\Resource\Requirement;

use Ymir\Cli\ExecutionContext;

interface RequirementInterface
{
    /**
     * Fulfills the requirement and returns the required parameter.
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []);
}
