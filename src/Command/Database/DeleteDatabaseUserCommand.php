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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;

class DeleteDatabaseUserCommand extends AbstractDatabaseCommand
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
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the database user to delete')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database user will be deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to create the new database user?', $input, $output);
        $username = $input->getStringArgument('username');
        $users = $this->apiClient->getDatabaseUsers($databaseServer['id']);

        if ($users->isEmpty()) {
            throw new RuntimeException('The database server doesn\'t have any managed database users');
        } elseif (empty($username)) {
            $username = (string) $output->choice('Which database user would you like to delete', $users->pluck('username'));
        }

        $user = $users->firstWhere('username', $username);

        if (empty($user['id'])) {
            throw new InvalidInputException(sprintf('No database user found with the "%s" username', $username));
        }

        if (!$output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" database user?', $user['username']), false)) {
            return;
        }

        $this->apiClient->deleteDatabaseUser($databaseServer['id'], $user['id']);

        $output->info('Database user deleted');

        if (!$databaseServer['publicly_accessible']) {
            $output->newLine();
            $output->important('The database user needs to be manually deleted on the database server because it isn\'t publicly accessible. You can use the following query to delete it:');
            $output->writeln(sprintf('DROP USER IF EXISTS %s@\'%%\'', $user['username']));
        }
    }
}
