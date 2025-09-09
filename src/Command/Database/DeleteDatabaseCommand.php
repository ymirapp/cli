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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DeleteDatabaseCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a database on a public database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The name of the database to delete')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database will be deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to delete a database from?');

        if (!$databaseServer->isPublic()) {
            throw new ResourceStateException('Database on private database servers need to be manually deleted');
        }

        $database = $this->resolve(Database::class, 'Which database would you like to delete?', $databaseServer);

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" database?', $database->getName()), false)) {
            return;
        }

        $this->apiClient->deleteDatabase($database);

        $this->output->info('Database deleted');
    }
}
