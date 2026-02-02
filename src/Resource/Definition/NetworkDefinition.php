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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\Network\CreateNetworkCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

class NetworkDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Network::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameSlugRequirement('What is the name of the network being created?'),
            'provider' => new CloudProviderRequirement('Which cloud provider should the network be on?'),
            'region' => new RegionRequirement('Which region should the network be created in?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'network';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createNetwork($fulfilledRequirements['provider'], $fulfilledRequirements['name'], $fulfilledRequirements['region']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): Network
    {
        $input = $context->getInput();
        $networkIdOrName = null;

        if ($input->hasArgument('network')) {
            $networkIdOrName = $input->getStringArgument('network');
        } elseif ($input->hasOption('network')) {
            $networkIdOrName = $input->getStringOption('network', true);
        }

        $networks = $context->getApiClient()->getNetworks($context->getTeam());

        if (!empty($fulfilledRequirements['region'])) {
            $networks = $networks->filter(function (Network $network) use ($fulfilledRequirements) {
                return $network->getRegion() === $fulfilledRequirements['region'];
            });
        }

        if ($networks->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no networks, but you can create one with the "%s" command', CreateNetworkCommand::NAME));
        } elseif (empty($networkIdOrName)) {
            $networkIdOrName = $context->getOutput()->choiceWithResourceDetails($question, $networks);
        }

        if (empty($networkIdOrName)) {
            throw new InvalidInputException('You must provide a valid network ID or name');
        } elseif (!is_numeric($networkIdOrName) && 1 < $networks->whereIdOrName($networkIdOrName)->count()) {
            throw new ResourceResolutionException(sprintf('Unable to select a network because more than one network has the name "%s"', $networkIdOrName));
        }

        $resolvedNetwork = $networks->firstWhereIdOrName($networkIdOrName);

        if (!$resolvedNetwork instanceof Network) {
            throw new ResourceNotFoundException($this->getResourceName(), $networkIdOrName);
        }

        return $resolvedNetwork;
    }
}
