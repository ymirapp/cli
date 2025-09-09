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

use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Network;

class NatGatewayRequirement extends AbstractRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): bool
    {
        if (empty($fulfilledRequirements['network']) || !$fulfilledRequirements['network'] instanceof Network) {
            throw new RequirementDependencyException('"network" must be fulfilled before fulfilling the nat gateway requirement');
        } elseif (!$fulfilledRequirements['network']->hasNatGateway() && !$context->getOutput()->confirm($this->question)) {
            throw new CommandCancelledException();
        }

        return true;
    }
}
