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
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

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
        $name = $this->getStringArgument($input, 'name');
        $user = $this->getStringArgument($input, 'user');
        $password = $this->getStringArgument($input, 'password');

        $databases = $this->apiClient->getDatabases($databaseServer['id']);

        if (empty($name)) {
            $name = $output->choice('Which database would you like to export?', $databases);
        } elseif (!$databases->has($name)) {
            throw new RuntimeException(sprintf('The "%s" database doesn\'t exist on the "%s" database server', $name, $databaseServer['name']));
        }

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

        $output->infoWithDelayWarning('Exporting database');

        Process::runShellCommandline(sprintf('mysqldump --quick --single-transaction --default-character-set=utf8mb4 --host=%s --user=%s --password=%s %s | gzip > %s', $databaseServer['endpoint'], $user, $password, $name, $filename));

        $output->infoWithValue('Database exported successfully to', $filename);
    }
}
