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
use Ymir\Cli\Resource\Model\Project;

class RegionRequirement extends AbstractRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        if (empty($fulfilledRequirements['provider'])) {
            throw new RequirementDependencyException('"provider" must be fulfilled before fulfilling the region requirement');
        }

        $apiClient = $context->getApiClient();
        $region = $context->getInput()->getStringOption('region', true);
        $regions = $apiClient->getRegions($fulfilledRequirements['provider']);

        if ($regions->isEmpty()) {
            throw new RequirementFulfillmentException('No cloud provider regions found');
        } elseif (!empty($region) && $regions->keys()->contains(strtolower($region))) {
            return $region;
        } elseif (!empty($region) && !$regions->keys()->contains(strtolower($region))) {
            throw new InvalidInputException('The given "region" isn\'t a valid cloud provider region');
        }

        $project = $context->getProject();

        return $project instanceof Project ? $project->getRegion() : $context->getOutput()->choice($this->question, $regions);
    }
}
