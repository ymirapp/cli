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
use Ymir\Cli\Command\Cache\CreateCacheCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\CacheClusterEngineRequirement;
use Ymir\Cli\Resource\Requirement\CacheClusterTypeRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\NatGatewayRequirement;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionNetworkRequirement;

class CacheClusterDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return CacheCluster::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameSlugRequirement('What is the name of the cache cluster being created?'),
            'network' => new ResolveOrProvisionNetworkRequirement('Which network should the cache cluster be created on?'),
            'nat_gateway' => new NatGatewayRequirement('A cache cluster will require Ymir to add a NAT gateway to your network (~$32/month). Would you like to proceed?'),
            'engine' => new CacheClusterEngineRequirement(),
            'type' => new CacheClusterTypeRequirement('Which type should the cache cluster be?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'cache cluster';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createCache($fulfilledRequirements['network'], $fulfilledRequirements['name'], $fulfilledRequirements['engine'], $fulfilledRequirements['type']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): CacheCluster
    {
        $cacheIdOrName = $context->getInput()->getStringArgument('cache');
        $caches = $context->getApiClient()->getCaches($context->getTeam());

        if (!empty($fulfilledRequirements['region'])) {
            $caches = $caches->filter(function (CacheCluster $cacheCluster) use ($fulfilledRequirements) {
                return $cacheCluster->getRegion() === $fulfilledRequirements['region'];
            });
        }

        if ($caches->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no cache clusters, but you can create one with the "%s" command', CreateCacheCommand::NAME));
        } elseif (empty($cacheIdOrName)) {
            $cacheIdOrName = $context->getOutput()->choiceWithResourceDetails($question, $caches);
        }

        if (empty($cacheIdOrName)) {
            throw new InvalidInputException('You must provide a valid cache cluster ID or name');
        } elseif (!is_numeric($cacheIdOrName) && 1 < $caches->whereIdOrName($cacheIdOrName)->count()) {
            throw new ResourceResolutionException(sprintf('Unable to select a cache cluster because more than one cache cluster has the name "%s"', $cacheIdOrName));
        }

        $resolvedCacheCluster = $caches->firstWhereIdOrName($cacheIdOrName);

        if (!$resolvedCacheCluster instanceof CacheCluster) {
            throw new ResourceNotFoundException($this->getResourceName(), $cacheIdOrName);
        }

        return $resolvedCacheCluster;
    }
}
