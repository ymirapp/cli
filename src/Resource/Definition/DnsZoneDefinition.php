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

namespace Ymir\Cli\Resource\Definition;

use Ymir\Cli\Command\Dns\CreateDnsZoneCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DnsZone;

class DnsZoneDefinition implements ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return DnsZone::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'DNS zone';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): DnsZone
    {
        $input = $context->getInput();
        $zoneIdOrName = $input->getStringArgument('zone') ?: $input->getStringOption('zone', true);

        $zones = $context->getApiClient()->getDnsZones($context->getTeam());

        if ($zones->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no DNS zones, but you can create one with the "%s" command', CreateDnsZoneCommand::NAME));
        }

        if (empty($zoneIdOrName)) {
            $zoneIdOrName = $context->getOutput()->choice($question, $zones->map(function (DnsZone $zone) {
                return $zone->getName();
            }));
        }

        if (empty($zoneIdOrName)) {
            throw new InvalidInputException('You must provide a valid DNS zone ID or name');
        }

        $resolvedZone = $zones->firstWhereIdOrName($zoneIdOrName);

        if (!$resolvedZone instanceof DnsZone) {
            throw new ResourceNotFoundException($this->getResourceName(), $zoneIdOrName);
        }

        return $resolvedZone;
    }
}
