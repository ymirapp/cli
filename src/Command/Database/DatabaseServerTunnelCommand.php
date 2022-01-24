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

namespace Ymir\Cli\Command\Database;

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

class DatabaseServerTunnelCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:tunnel';

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
            ->setDescription('Create a SSH tunnel to a database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to create a SSH tunnel to')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'The local port to use to connect to the database server', '3305');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to connect to', $input, $output);

        if ('available' !== $databaseServer['status']) {
            throw new RuntimeException(sprintf('The "%s" database server isn\'t available', $databaseServer['name']));
        } elseif ($databaseServer['publicly_accessible']) {
            throw new RuntimeException(sprintf('The "%s" database server is publicly accessible and isn\'t on a private subnet', $databaseServer['name']));
        }

        $network = $this->apiClient->getNetwork(Arr::get($databaseServer, 'network.id'));

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The database server network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        }

        $localPort = (int) $this->getNumericOption($input, 'port');

        if (3306 === $localPort) {
            throw new RuntimeException('Cannot use port 3306 as the local port for the SSH tunnel to the database server');
        }

        $output->info(sprintf('Creating SSH tunnel to the "<comment>%s</comment>" database server. You can connect using: <comment>localhost:%s</comment>', $databaseServer['name'], $localPort));

        $tunnel = Ssh::tunnelBastionHost($network->get('bastion_host'), $localPort, $databaseServer['endpoint'], 3306);

        $output->info('Once finished, press "<comment>Ctrl+C</comment>" to close the tunnel');

        $tunnel->wait();
    }
}
