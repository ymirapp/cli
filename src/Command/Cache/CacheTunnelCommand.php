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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
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
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The path to the user's home directory.
     *
     * @var string
     */
    private $homeDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, string $homeDirectory, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->homeDirectory = rtrim($homeDirectory, '/');
    }

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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $cache = $this->determineCache('Which cache cluster would you like to connect to', $input, $output);

        if ('available' !== $cache['status']) {
            throw new RuntimeException(sprintf('The "%s" cache isn\'t available', $cache['name']));
        }

        $network = $this->apiClient->getNetwork(Arr::get($cache, 'network.id'));

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The cache network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        }

        $localPort = (int) $this->getNumericOption($input, 'port');

        if (6379 === $localPort) {
            throw new RuntimeException('Cannot use port 6379 as the local port for the SSH tunnel to the cache cluster');
        }

        $output->info(sprintf('Creating SSH tunnel to the "<comment>%s</comment>" cache cluster. You can connect using: <comment>localhost:%s</comment>', $cache['name'], $localPort));

        $tunnel = Ssh::tunnelBastionHost($network->get('bastion_host'), $localPort, $cache['endpoint'], 6379);

        $output->info('Once finished, press "<comment>Ctrl+C</comment>" to close the tunnel');

        $tunnel->wait();
    }
}
