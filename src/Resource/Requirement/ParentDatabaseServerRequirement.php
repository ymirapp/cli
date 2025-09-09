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
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

class ParentDatabaseServerRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ResourceModelInterface
    {
        $parentResource = $context->getParentResource();

        if (!$parentResource instanceof DatabaseServer) {
            throw new LogicException('A DatabaseServer must be resolved and passed into the context before fulfilling its dependencies');
        }

        return $parentResource;
    }
}
