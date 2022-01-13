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

namespace Ymir\Cli\Command\Cache;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputInterface;

abstract class AbstractCacheCommand extends AbstractCommand
{
    /**
     * Determine the cache that the command is interacting with.
     */
    protected function determineCache(string $question, InputInterface $input, OutputInterface $output): array
    {
        $caches = $this->apiClient->getCaches($this->cliConfiguration->getActiveTeamId());
        $cacheIdOrName = $this->getStringArgument($input, 'cache');

        if ($caches->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no cache clusters. You can create one with the "%s" command.', CreateCacheCommand::NAME));
        } elseif (empty($cacheIdOrName)) {
            $cacheIdOrName = $output->choiceWithResourceDetails($question, $caches);
        }

        $cache = $caches->firstWhere('id', $cacheIdOrName) ?? $caches->firstWhere('name', $cacheIdOrName);

        if (1 < $caches->where('name', $cacheIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a cache cluster because more than one cache cluster has the name "%s"', $cacheIdOrName));
        } elseif (!is_array($cache) || empty($cache['id'])) {
            throw new RuntimeException(sprintf('Unable to find a cache cluster with "%s" as the ID or name', $cacheIdOrName));
        }

        return $cache;
    }
}
