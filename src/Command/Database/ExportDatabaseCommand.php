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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Database\Mysqldump;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Process\Process;

class ExportDatabaseCommand extends AbstractDatabaseTunnelCommand
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
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Filesystem $filesystem, SshExecutable $sshExecutable)
    {
        parent::__construct($apiClient, $contextFactory, $sshExecutable);

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
    protected function perform()
    {
        $compression = $this->input->getStringOption('compression');

        if (!in_array($compression, ['gzip', 'none'])) {
            throw new InvalidInputException('The compression method must be either "gzip" or "none"');
        }

        $connection = $this->getConnection('Which <comment>%s</comment> database would you like to export?', 'Which database server would you like to export a database from?');
        $filename = sprintf('%s_%s.%s', $connection->getDatabase(), Carbon::now()->toDateString(), 'gzip' === $compression ? 'sql.gz' : 'sql');

        if ($this->filesystem->exists($filename) && !$this->output->confirm(sprintf('The "<comment>%s</comment>" backup file already exists. Do you want to overwrite it?', $filename), false)) {
            return;
        }

        $tunnel = null;

        if ($connection->needsSshTunnel()) {
            $tunnel = $this->openSshTunnel($connection->getDatabaseServer());
        }

        $this->output->infoWithDelayWarning(sprintf('Exporting "<comment>%s</comment>" database', $connection->getDatabase()));

        try {
            Mysqldump::fromConnection($connection, ['compress' => $compression])->start($filename);
        } catch (\Throwable $exception) {
            throw new SystemException(sprintf('Failed to export database: %s', $exception->getMessage()));
        } finally {
            if ($tunnel instanceof Process) {
                $tunnel->stop();
            }
        }

        $this->output->infoWithValue('Database exported successfully to', $filename);
    }
}
