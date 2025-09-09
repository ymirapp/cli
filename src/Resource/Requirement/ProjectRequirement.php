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

use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Project;

class ProjectRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): Project
    {
        $project = $context->getParentResource() ?? $context->getProject();

        if (!$project instanceof Project) {
            throw new LogicException('A project must be resolved and existing in the context before fulfilling this requirement');
        }

        return $project;
    }
}
