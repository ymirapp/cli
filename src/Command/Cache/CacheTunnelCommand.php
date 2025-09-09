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
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Resource\Model\BastionHost;
use Ymir\Cli\Resource\Model\CacheCluster;

class CacheTunnelCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:tunnel';

    /**
     * The SSH executable.
     *
     * @var SshExecutable
     */
    private $sshExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, SshExecutable $sshExecutable)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->sshExecutable = $sshExecutable;
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
    protected function perform()
    {
        $cacheCluster = $this->resolve(CacheCluster::class, 'Which cache cluster would you like to connect to?');
        $localPort = (int) $this->input->getNumericOption('port');

        if ('available' !== $cacheCluster->getStatus()) {
            throw new InvalidInputException(sprintf('The "%s" cache cluster isn\'t available', $cacheCluster->getName()));
        } elseif (empty($localPort)) {
            throw new InvalidInputException('You must provide a valid "port" option');
        } elseif (6379 === $localPort) {
            throw new InvalidInputException('Cannot use port 6379 as the local port for the SSH tunnel to the cache cluster');
        }

        $network = $cacheCluster->getNetwork();

        if (!$network->getBastionHost() instanceof BastionHost) {
            throw new ResourceStateException(sprintf('The cache cluster network doesn\'t have a bastion host to connect to, but you can add one to the network with the "%s" command', AddBastionHostCommand::NAME));
        }

        $this->output->info(sprintf('Opening SSH tunnel to the "<comment>%s</comment>" cache cluster...', $cacheCluster->getName()));

        $tunnel = $this->sshExecutable->openTunnelToBastionHost($network->getBastionHost(), $localPort, $cacheCluster->getEndpoint(), 6379);

        $this->output->newLine();
        $this->output->info(sprintf('SSH tunnel to the "<comment>%s</comment>" cache cluster opened', $cacheCluster->getName()));
        $this->output->writeln(sprintf('<info>Local endpoint:</info> 127.0.0.1:%s', $localPort));
        $this->output->newLine();
        $this->output->writeln('The tunnel will remain open as long as this command is running. Press <comment>Ctrl+C</comment> to close the tunnel.');

        $tunnel->wait();
    }
}
