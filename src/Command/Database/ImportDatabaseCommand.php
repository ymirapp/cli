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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Database\Connection;
use Ymir\Cli\Database\PDO;
use Ymir\Cli\Exception\Executable\ExecutableNotDetectedException;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\Executable\PsqlExecutable;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Resource\Model\DatabaseServer;

class ImportDatabaseCommand extends AbstractDatabaseTunnelCommand
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
     * The psql executable.
     *
     * @var PsqlExecutable
     */
    private $psqlExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Filesystem $filesystem, PsqlExecutable $psqlExecutable, SshExecutable $sshExecutable)
    {
        parent::__construct($apiClient, $contextFactory, $sshExecutable);

        $this->filesystem = $filesystem;
        $this->psqlExecutable = $psqlExecutable;
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
        $connection = $this->getConnection('Which <comment>%s</comment> database would you like to import the SQL backup to?', 'Which database server would you like to import a database to?');
        $tunnel = null;

        if ($connection->needsSshTunnel()) {
            $tunnel = $this->openSshTunnel($connection->getDatabaseServer());
        }

        $this->output->infoWithDelayWarning(sprintf('Importing "<comment>%s</comment>" to the "<comment>%s</comment>" database', $filename, $connection->getDatabase()));

        try {
            $this->importBackup($connection, $filename);
        } finally {
            if ($tunnel instanceof Process) {
                $tunnel->stop();
            }
        }

        $this->output->info('Database imported successfully');
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
    private function importBackup(Connection $connection, string $filename): void
    {
        try {
            $engine = $connection->getDatabaseServer()->getEngine();

            switch ($engine) {
                case DatabaseServer::ENGINE_MYSQL:
                    $this->importMysqlBackup($connection, $filename);

                    break;
                case DatabaseServer::ENGINE_POSTGRESQL:
                    $this->importPostgresqlBackup($connection, $filename);

                    break;
                default:
                    throw new UnsupportedDatabaseServerEngineException($engine);
            }
        } catch (SystemException|ExecutableNotDetectedException|UnsupportedDatabaseServerEngineException $exception) {
            throw $exception;
        } catch (\PDOException $exception) {
            throw new SystemException(sprintf('Failed to import database: %s', $exception->getMessage()));
        } catch (\Throwable $exception) {
            throw new SystemException($exception->getMessage());
        }
    }

    /**
     * Imports the given SQL backup file using the given MySQL connection.
     */
    private function importMysqlBackup(Connection $connection, string $filename): void
    {
        $isCompressed = str_ends_with($filename, '.gz');
        $fclose = $isCompressed ? 'gzclose' : 'fclose';
        $feof = $isCompressed ? 'gzeof' : 'feof';
        $fgets = $isCompressed ? 'gzgets' : 'fgets';
        $fopen = $isCompressed ? 'gzopen' : 'fopen';
        $file = $fopen($filename, 'r');

        if (!is_resource($file)) {
            throw new SystemException(sprintf('Failed to open file: %s', $filename));
        }

        try {
            $lines = LazyCollection::make(function () use (&$file, $feof, $fgets) {
                while (!$feof($file)) {
                    yield $fgets($file);
                }
            });
            $pdo = PDO::fromConnection($connection);
            $query = '';

            $lines->each(function ($line) use ($pdo, &$query): void {
                if (!is_string($line)) {
                    return;
                }

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
        } finally {
            if (is_resource($file)) {
                $fclose($file);
            }
        }
    }

    /**
     * Imports the given SQL backup file using the given PostgreSQL connection.
     */
    private function importPostgresqlBackup(Connection $connection, string $filename): void
    {
        $fclose = str_ends_with($filename, '.gz') ? 'gzclose' : 'fclose';
        $fopen = str_ends_with($filename, '.gz') ? 'gzopen' : 'fopen';
        $file = $fopen($filename, 'r');

        if (!is_resource($file)) {
            throw new SystemException(sprintf('Failed to open file: %s', $filename));
        }

        try {
            $this->psqlExecutable->import($connection, $file);
        } finally {
            if (is_resource($file)) {
                $fclose($file);
            }
        }
    }
}
