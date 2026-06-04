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

use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;

class ServerlessDatabaseServerRequirement extends AbstractDatabaseServerRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): bool
    {
        if (empty($fulfilledRequirements['engine']) || !is_string($fulfilledRequirements['engine'])) {
            throw new RequirementDependencyException('"engine" must be fulfilled before fulfilling the serverless database server requirement');
        }

        $input = $context->getInput();

        if ($input->getBooleanOption('serverless')) {
            return true;
        }

        $type = $input->getStringOption('type');

        if (null !== $type) {
            return $this->isAuroraDatabaseTypeCompatibleWithEngine($type, $fulfilledRequirements['engine']);
        }

        return $input->isInteractive() && $context->getOutput()->confirm($this->question, false);
    }
}
