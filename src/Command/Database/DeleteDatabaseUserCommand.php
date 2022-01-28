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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\OutputInterface;

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
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server where the database user will be deleted')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the database user to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to create the new database user?', $input, $output);
        $username = $this->getStringArgument($input, 'username');
        $users = $this->apiClient->getDatabaseUsers($databaseServer['id']);

        if ($users->isEmpty()) {
            throw new InvalidArgumentException('The database server doesn\'t have any managed database users');
        } elseif (empty($username)) {
            $username = (string) $output->choice('Which database user would you like to delete', $users->pluck('username'));
        }

        $user = $users->firstWhere('username', $username);

        if (empty($user['id'])) {
            throw new InvalidArgumentException(sprintf('No database user found with the "%s" username', $username));
        }

        if (!$output->confirm('Are you sure you want to delete this database user?', false)) {
            return;
        }

        $this->apiClient->deleteDatabaseUser($databaseServer['id'], $user['id']);

        $output->info('Database user deleted');

        if (!$databaseServer['publicly_accessible']) {
            $output->newLine();
            $output->important('The database user needs to be manually deleted on the database server because it isn\'t publicly accessible');
        }
    }
}
