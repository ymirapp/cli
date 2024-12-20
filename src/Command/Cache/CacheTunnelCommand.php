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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\Ssh;

class CacheTunnelCommand extends AbstractCacheCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:tunnel';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a SSH tunnel to a cache cluster')
            ->addArgument('cache', InputArgument::OPTIONAL, 'The ID or name of the cache cluster to create a SSH tunnel to')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'The local port to use to connect to the cache cluster', '6378');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $cache = $this->determineCache('Which cache cluster would you like to connect to');

        if ('available' !== $cache['status']) {
            throw new RuntimeException(sprintf('The "%s" cache isn\'t available', $cache['name']));
        }

        $network = $this->apiClient->getNetwork(Arr::get($cache, 'network.id'));

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The cache network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        }

        $localPort = (int) $this->input->getNumericOption('port');

        if (6379 === $localPort) {
            throw new RuntimeException('Cannot use port 6379 as the local port for the SSH tunnel to the cache cluster');
        }

        $this->output->info(sprintf('Creating SSH tunnel to the "<comment>%s</comment>" cache cluster. You can connect using: <comment>localhost:%s</comment>', $cache['name'], $localPort));

        $tunnel = Ssh::tunnelBastionHost($network->get('bastion_host'), $localPort, $cache['endpoint'], 6379);

        $this->output->info('Once finished, press "<comment>Ctrl+C</comment>" to close the tunnel');

        $tunnel->wait();
    }
}
