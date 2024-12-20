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
use Ymir\Cli\Command\Network\RemoveNatGatewayCommand;

class DeleteCacheCommand extends AbstractCacheCommand
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
        $cache = $this->determineCache('Which cache cluster would you like to delete');

        if (!$this->output->confirm('Are you sure you want to delete this cache cluster?', false)) {
            return;
        }

        $this->apiClient->deleteCache($cache['id']);

        $this->output->infoWithDelayWarning('Cache cluster deleted');
        $this->output->newLine();
        $this->output->note(sprintf('If you have no other resources using the private subnet, you should remove the network\'s NAT gateway using the "<comment>%s</comment>" command', RemoveNatGatewayCommand::NAME));
    }
}
