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

use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Database\Connection;
use Ymir\Cli\Database\PDO;
use Ymir\Cli\Exception\InvalidInputException;
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
            ->setDescription('Import a local SQL backup to a database')
            ->addArgument('filename', InputArgument::REQUIRED, 'The path to the local .sql or .sql.gz file')
            ->addArgument('database', InputArgument::OPTIONAL, 'The database name to import into')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server to import a database to')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The user used to connect to the database server')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The password of the user connecting to the database server');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $filename = $this->getFilename();
        $connection = $this->getConnection();
        $tunnel = null;

        if ($connection->needsSshTunnel()) {
            $tunnel = $this->startSshTunnel($connection->getDatabaseServer());
        }

        $this->output->infoWithDelayWarning(sprintf('Importing "<comment>%s</comment>" to the "<comment>%s</comment>" database', $filename, $connection->getDatabase()));

        $this->importBackup($connection, $filename);

        if ($tunnel instanceof Process) {
            $tunnel->stop();
        }

        $this->output->info('Database imported successfully');
    }

    /**
     * Get the connection to the database by prompting for any missing information.
     */
    private function getConnection(): Connection
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to import a database to?');
        $database = $this->input->getStringArgument('database');

        if (empty($database) && !$databaseServer['publicly_accessible']) {
            throw new RuntimeException('You must specify the database name to import the SQL backup to for a private database server');
        } elseif (empty($database)) {
            $database = $this->output->choice('Which database would you like to import the SQL backup to?', $this->apiClient->getDatabases($databaseServer['id']));
        }

        $user = $this->determineUser();
        $password = $this->determinePassword($user);

        return new Connection($database, $databaseServer, $user, $password);
    }

    /**
     * Get the filename of the SQL backup to import.
     */
    private function getFilename(): string
    {
        $filename = $this->input->getStringArgument('filename');

        if (!str_ends_with($filename, '.sql') && !str_ends_with($filename, '.sql.gz')) {
            throw new InvalidInputException('You may only import .sql or .sql.gz files');
        } elseif (!$this->filesystem->exists($filename)) {
            throw new InvalidInputException(sprintf('File "%s" doesn\'t exist', $filename));
        }

        return $filename;
    }

    /**
     * Imports the given SQL backup file using the given database connection.
     */
    private function importBackup(Connection $connection, string $filename)
    {
        try {
            $isCompressed = str_ends_with($filename, '.gz');

            $fclose = $isCompressed ? 'gzclose' : 'fclose';
            $feof = $isCompressed ? 'gzeof' : 'feof';
            $fgets = $isCompressed ? 'gzgets' : 'fgets';
            $fopen = $isCompressed ? 'gzopen' : 'fopen';

            $file = $fopen($filename, 'r');

            if (!is_resource($file)) {
                throw new RuntimeException('Failed to open file: '.$filename);
            }

            $lines = LazyCollection::make(function () use (&$file, $feof, $fgets) {
                while (!$feof($file)) {
                    yield $fgets($file);
                }
            });
            $pdo = PDO::fromConnection($connection);
            $query = '';

            $lines->each(function ($line) use ($pdo, &$query) {
                $line = trim($line);

                if (str_starts_with($line, '--') || empty($line)) {
                    return;
                }

                $query .= $line;

                if (str_ends_with(trim($line), ';')) {
                    $pdo->exec($query);
                    $query = '';
                }
            });
        } catch (\Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            $message = $exception->getMessage();

            if ($exception instanceof \PDOException) {
                $message = 'Failed to import database: '.$message;
            }

            throw new RuntimeException($message);
        } finally {
            if (isset($file, $fclose) && is_resource($file)) {
                $fclose($file);
            }
        }
    }
}
