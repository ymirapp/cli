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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Database\Connection;
use Ymir\Cli\Database\Mysqldump;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

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
            ->setDescription('Export a database to a local SQL file')
            ->addArgument('database', InputArgument::OPTIONAL, 'The database name to export from')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server to export a database from')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The user used to connect to the database server')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The password of the user connecting to the database server')
            ->addOption('compression', null, InputOption::VALUE_REQUIRED, 'The compression method to use when exporting the database (gzip or none)', 'gzip');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $compression = $input->getStringOption('compression');

        if (!in_array($compression, ['gzip', 'none'])) {
            throw new InvalidInputException('The compression method must be either "gzip" or "none"');
        }

        $connection = $this->getConnection($input, $output);
        $filename = sprintf('%s_%s.%s', $connection->getDatabase(), Carbon::now()->toDateString(), 'gzip' === $compression ? 'sql.gz' : 'sql');

        if ($this->filesystem->exists($filename) && !$output->confirm(sprintf('The "<comment>%s</comment>" backup file already exists. Do you want to overwrite it?', $filename), false)) {
            return;
        }

        $tunnel = null;

        if (!$connection->needsSshTunnel()) {
            $tunnel = $this->startSshTunnel($connection->getDatabaseServer(), $output);
        }

        $output->infoWithDelayWarning(sprintf('Exporting "<comment>%s</comment>" database', $connection->getDatabase()));

        try {
            Mysqldump::fromConnection($connection, ['compress' => $compression])->start($filename);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Failed to export database: '.$exception->getMessage());
        } finally {
            if ($tunnel instanceof Process) {
                $tunnel->stop();
            }
        }

        $output->infoWithValue('Database exported successfully to', $filename);
    }

    /**
     * Get the connection to the database by prompting for any missing information.
     */
    private function getConnection(Input $input, Output $output): Connection
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to export a database from?', $input, $output);
        $database = $input->getStringArgument('database');

        if (empty($database) && !$databaseServer['publicly_accessible']) {
            throw new RuntimeException('You must specify the database name to export for a private database server');
        } elseif (empty($database)) {
            $database = $output->choice('Which database would you like to export?', $this->apiClient->getDatabases($databaseServer['id']));
        }

        $user = $this->determineUser($input, $output);
        $password = $this->determinePassword($input, $output, $user);

        return new Connection($database, $databaseServer, $user, $password);
    }
}
