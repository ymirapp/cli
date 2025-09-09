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

use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Network;

class CacheClusterTypeRequirement extends AbstractRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        if (empty($fulfilledRequirements['engine'])) {
            throw new RequirementDependencyException('"engine" must be fulfilled before fulfilling the cache cluster type requirement');
        }

        if (empty($fulfilledRequirements['network']) || !$fulfilledRequirements['network'] instanceof Network) {
            throw new RequirementDependencyException('"network" must be fulfilled before fulfilling the cache cluster type requirement');
        }

        $type = $context->getInput()->getStringOption('type');
        $types = $context->getApiClient()->getCacheTypes($fulfilledRequirements['network']->getProvider())->map(function (array $details) use ($fulfilledRequirements) {
            return sprintf('%s vCPU, %sGiB RAM (~$%s/month)', $details['cpu'], $details['ram'], $details['price'][$fulfilledRequirements['engine']]);
        });

        if ($types->isEmpty()) {
            throw new RequirementFulfillmentException('No cache cluster types found');
        } elseif (null === $type) {
            $type = $context->getOutput()->choice($this->question, $types);
        }

        if (!$types->has($type)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid cache cluster type', $type));
        }

        return $type;
    }
}
