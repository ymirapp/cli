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
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class ImportDatabaseCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:import';

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
            ->setDescription('Import a local .sql or .sql.gz file to a database')
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the local .sql or .sql.gz file')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to import a database to')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database to import')
            ->addArgument('user', InputArgument::OPTIONAL, 'The user used to connect to the database server')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the user connecting to the database server');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $file = $this->getStringArgument($input, 'file');

        if (!str_ends_with($file, '.sql') && !str_ends_with($file, '.sql.gz')) {
            throw new RuntimeException('You may only import .sql or .sql.gz files');
        } elseif (!$this->filesystem->exists($file)) {
            throw new RuntimeException(sprintf('File "%s" doesn\'t exist', $file));
        }

        $databaseServer = $this->determineDatabaseServer('Which database server would you like to import a database to?', $input, $output);
        $host = $databaseServer['endpoint'];
        $name = $this->determineDatabaseName($databaseServer, $input, $output);
        $port = 3306;
        $tunnel = null;

        $user = $this->determineUser($input, $output);
        $password = $this->determinePassword($input, $output, $user);

        if (!$databaseServer['publicly_accessible']) {
            $output->info(sprintf('Opening SSH tunnel to "<comment>%s</comment>" database server', $databaseServer['name']));

            $tunnel = $this->startSshTunnel($databaseServer);
            $host = '127.0.0.1';
            $port = '3305';
        }

        $output->infoWithDelayWarning(sprintf('Importing "<comment>%s</comment>" to the "<comment>%s</comment>" database', $file, $name));

        $command = sprintf('%s %s | mysql --host=%s --port=%s --user=%s --password=%s %s', str_ends_with($file, '.sql.gz') ? 'gunzip <' : 'cat', $file, $host, $port, $user, $password, $name);

        Process::runShellCommandline($command);

        if ($tunnel instanceof Process) {
            $tunnel->stop();
        }

        $output->info('Database imported successfully');
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
            throw new RuntimeException('You must specify the name of the database to import the SQL file to for a private database server');
        }

        return $output->choice('Which database would you like to import the SQL file to?', $this->apiClient->getDatabases($databaseServer['id']));
    }
}
