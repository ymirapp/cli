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
use Ymir\Cli\Resource\Model\Project;

class EnvironmentsRequirement implements RequirementInterface
{
    /**
     * {@inheritDoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): array
    {
        return Project::DEFAULT_ENVIRONMENTS;
    }
}
