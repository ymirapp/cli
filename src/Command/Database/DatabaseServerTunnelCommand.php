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
use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Arr;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\ProjectConfiguration;

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
            ->addArgument('port', InputArgument::OPTIONAL, 'The local port to use to connect to the database server', '3305');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to connect to', $input, $output);

        if ('available' !== $databaseServer['status']) {
            throw new RuntimeException(sprintf('The "%s" database server isn\'t available', $databaseServer['name']));
        } elseif ($databaseServer['publicly_accessible']) {
            throw new RuntimeException(sprintf('The "%s" database server is publicly accessible and isn\'t on a private subnet', $databaseServer['name']));
        }

        $network = $this->apiClient->getNetwork($databaseServer['network']['id']);

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The database server network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        } elseif (!is_dir($this->homeDirectory.'/.ssh')) {
            $this->filesystem->mkdir($this->homeDirectory.'/.ssh', 0700);
        }

        $localPort = $this->getNumericArgument($input, 'port');

        if (3306 === $localPort) {
            throw new RuntimeException('Cannot use port 3306 as the local port for the SSH tunnel to the database server');
        }

        $privateKeyFilename = $this->homeDirectory.'/.ssh/ymir-database-server-tunnel';

        $this->filesystem->dumpFile($privateKeyFilename, Arr::get($network, 'bastion_host.private_key'));
        $this->filesystem->chmod($privateKeyFilename, 0600);

        $output->writeln(sprintf('<info>Creating SSH tunnel to the </info> "<comment>%s</comment>" <info>database server. You can connect using: <comment>localhost:%s</comment>', $databaseServer['name'], $localPort));

        passthru(sprintf('ssh ec2-user@%s -i %s -o LogLevel=error -L %s:%s:3306 -N', Arr::get($network, 'bastion_host.endpoint'), $privateKeyFilename, $localPort, $databaseServer['endpoint']));
    }
}
