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

namespace Ymir\Cli\Project\Initialization;

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\CacheConfigurationChange;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Resource\Model\CacheCluster;

class CacheInitializationStep implements InitializationStepInterface
{
    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        $cacheCluster = $this->selectCacheCluster($context, $projectRequirements['region']);

        return $cacheCluster instanceof CacheCluster ? new CacheConfigurationChange($cacheCluster->getName()) : null;
    }

    /**
     * Select or provision a cache cluster in the given region.
     */
    private function selectCacheCluster(ExecutionContext $context, string $region): ?CacheCluster
    {
        $output = $context->getOutput();

        if (!$output->confirm('Would you like to use a cache cluster for this project?', false)) {
            return null;
        }

        $apiClient = $context->getApiClient();
        $cacheCluster = null;
        $cacheClusters = $apiClient->getCaches($context->getTeam())->filter(function (CacheCluster $cacheCluster) use ($region): bool {
            return $region === $cacheCluster->getRegion();
        })->filter(function (CacheCluster $cacheCluster): bool {
            return !in_array($cacheCluster->getStatus(), ['deleting', 'failed']);
        });

        $provisionPrompt = $cacheClusters->isEmpty()
            ? sprintf('Your team doesn\'t have any configured cache clusters in the "<comment>%s</comment>" region. Would you like to create one for this team first?', $region)
            : 'Would you like to create a new one for this project instead?';

        if (!$cacheClusters->isEmpty() && $output->confirm('Would you like to use an existing cache cluster for this project?')) {
            $cacheCluster = $cacheClusters->firstWhereIdOrName($output->choiceWithResourceDetails('Which cache cluster would you like to use?', $cacheClusters));
        } elseif ($output->confirm($provisionPrompt)) {
            $cacheCluster = $context->provision(CacheCluster::class);
        }

        return $cacheCluster instanceof CacheCluster ? $cacheCluster : null;
    }
}
