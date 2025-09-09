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
use Ymir\Cli\Project\Configuration\CacheConfigurationChange;
use Ymir\Cli\Resource\Model\CacheCluster;

class CreateCacheCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new cache cluster')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the cache cluster')
            ->addOption('engine', null, InputOption::VALUE_REQUIRED, 'The engine used by the cache cluster', 'valkey')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the cache cluster will be created')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The cache cluster type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $cacheCluster = $this->provision(CacheCluster::class);

        $this->output->infoWithDelayWarning('Cache cluster created');

        if ($this->getProjectConfiguration()->exists() && $this->output->confirm('Would you like to add the cache cluster to your project configuration?')) {
            $this->getProjectConfiguration()->applyChangesToEnvironments(new CacheConfigurationChange($cacheCluster->getName()));
        }
    }
}
