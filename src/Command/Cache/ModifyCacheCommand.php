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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Model\CacheCluster;
use Ymir\Cli\Resource\Requirement\CacheClusterTypeRequirement;

class ModifyCacheCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:modify';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Modify a cache cluster')
            ->addArgument('cache', InputArgument::OPTIONAL, 'The ID or name of the cache cluster to modify')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The cache cluster type');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $cacheCluster = $this->resolve(CacheCluster::class, 'Which cache cluster would you like to modify?');
        $newType = $this->fulfill(new CacheClusterTypeRequirement(sprintf('What should the cache cluster type be changed to? <fg=default>(Currently: <comment>%s</comment>)</>', $cacheCluster->getType())), [
            'engine' => $cacheCluster->getEngine(),
            'network' => $cacheCluster->getNetwork(),
        ]);

        if ($newType === $cacheCluster->getType()) {
            throw new InvalidInputException(sprintf('The cache cluster is already a "%s" type', $cacheCluster->getType()));
        } elseif (!$this->output->warningConfirmation('Modifying the cache cluster will cause your cache cluster to become unavailable for a few minutes')) {
            return;
        }

        $this->apiClient->updateCache($cacheCluster, $newType);

        $this->output->infoWithDelayWarning('Cache cluster modified');
    }
}
