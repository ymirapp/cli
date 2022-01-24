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

use Carbon\Carbon;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\Ssh;

class ExportDatabaseCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:export';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Export a database')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to export a database from')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database to export')
            ->addArgument('user', InputArgument::OPTIONAL, 'The user used to connect to the database server')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the user connecting to the database server');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to export a database from?', $input, $output);
        $name = $this->determineDatabaseName($databaseServer, $input, $output);
        $user = $this->getStringArgument($input, 'user');
        $password = $this->getStringArgument($input, 'password');

        if (empty($user)) {
            $user = $output->ask('Which user do you want to use to connect to the database server?', 'ymir');
        }

        if (empty($password)) {
            $password = $output->askHidden(sprintf('What\'s the "<comment>%s</comment>" password?', $user));
        }

        $filename = sprintf('%s_%s.sql.gz', $name, Carbon::now()->toDateString());

        if ($this->filesystem->exists($filename) && !$output->confirm(sprintf('The "<comment>%s</comment>" backup file already exists. Do you want to overwrite it?', $filename), false)) {
            return;
        }

        $host = $databaseServer['endpoint'];
        $port = 3306;
        $tunnel = null;

        if (!$databaseServer['publicly_accessible']) {
            $output->info(sprintf('Opening SSH tunnel to "<comment>%s</comment>" database server', $databaseServer['name']));

            $tunnel = $this->startSshTunnel($databaseServer);
            $host = '127.0.0.1';
            $port = '3305';

            // Need to wait a bit while SSH connection opens
            sleep(1);
        }

        $output->infoWithDelayWarning(sprintf('Exporting "<comment>%s</comment>" database', $name));

        Process::runShellCommandline(sprintf('mysqldump --quick --single-transaction --default-character-set=utf8mb4 --host=%s --port=%s --user=%s --password=%s %s | gzip > %s', $host, $port, $user, $password, $name, $filename));

        if ($tunnel instanceof Process) {
            $tunnel->stop();
        }

        $output->infoWithValue('Database exported successfully to', $filename);
    }

    /**
     * Determine the name of the database to export.
     */
    private function determineDatabaseName(array $databaseServer, InputInterface $input, OutputInterface $output): string
    {
        $name = $this->getStringArgument($input, 'name');

        if (!empty($name)) {
            return $name;
        } elseif (empty($name) && !$databaseServer['publicly_accessible']) {
            throw new RuntimeException('You must specify the name of the database to export for a private database server');
        }

        return $output->choice('Which database would you like to export?', $this->apiClient->getDatabases($databaseServer['id']));
    }

    /**
     * Start a SSH tunnel to a private database server.
     */
    private function startSshTunnel(array $databaseServer): Process
    {
        $network = $this->apiClient->getNetwork(Arr::get($databaseServer, 'network.id'));

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The database server network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        }

        return Ssh::tunnelBastionHost($network->get('bastion_host'), 3305, $databaseServer['endpoint'], 3306);
    }
}
