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
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;

class DeleteDatabaseUserCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:user:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a user on a database')
            ->addArgument('user', InputArgument::OPTIONAL, 'The username of the database user to delete')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database user will be deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to delete a database user from?');
        $databaseUser = $this->resolve(DatabaseUser::class, 'Which <comment>%s</comment> database user would you like to delete?', $databaseServer);

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" database user?', $databaseUser->getName()), false)) {
            return;
        }

        $this->apiClient->deleteDatabaseUser($databaseUser);

        $this->output->info('Database user deleted');

        if (!$databaseServer->isPublic()) {
            $this->output->newLine();
            $this->output->important('The database user needs to be manually deleted on the database server because it isn\'t publicly accessible. You can use the following query to delete it:');
            $this->output->writeln(sprintf('DROP USER IF EXISTS %s@\'%%\'', $databaseUser->getName()));
        }
    }
}
