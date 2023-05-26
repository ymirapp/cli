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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\OutputInterface;
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
            ->setDescription('Export a database to a local .sql.gz file')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database to export')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server to export a database from')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The user used to connect to the database server')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The password of the user connecting to the database server');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to export a database from?', $input, $output);
        $name = $this->determineDatabaseName($databaseServer, $input, $output);
        $user = $this->determineUser($input, $output);
        $password = $this->determinePassword($input, $output, $user);

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
        }

        $output->infoWithDelayWarning(sprintf('Exporting "<comment>%s</comment>" database', $name));

        Process::runShellCommandline(sprintf('mysqldump --quick --single-transaction --skip-add-locks --default-character-set=utf8mb4 --host=%s --port=%s --user=%s --password=%s %s | gzip > %s', $host, $port, $user, $password, $name, $filename), null, null);

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
        } elseif (!$databaseServer['publicly_accessible']) {
            throw new RuntimeException('You must specify the name of the database to export for a private database server');
        }

        return $output->choice('Which database would you like to export?', $this->apiClient->getDatabases($databaseServer['id']));
    }
}
