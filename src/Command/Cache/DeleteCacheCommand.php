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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Network\RemoveNatGatewayCommand;
use Ymir\Cli\Resource\Model\CacheCluster;

class DeleteCacheCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a cache cluster')
            ->addArgument('cache', InputArgument::OPTIONAL, 'The ID or name of the cache cluster to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $cacheCluster = $this->resolve(CacheCluster::class, 'Which cache cluster would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "%s" cache cluster?', $cacheCluster->getName()), false)) {
            return;
        }

        $this->apiClient->deleteCache($cacheCluster);

        $this->output->infoWithDelayWarning('Cache cluster deleted');
        $this->output->newLine();
        $this->output->note(sprintf('If you have no other resources using the private subnet, you should remove the network\'s NAT gateway using the "<comment>%s</comment>" command', RemoveNatGatewayCommand::NAME));
    }
}
