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
use Ymir\Cli\Console\OutputStyle;

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
            ->setDescription('Create a new user on a database')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server where the database user will be deleted')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new database user');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $database = $this->determineDatabaseServer('On which database server would you like to delete a database user?', $input, $output);
        $username = $this->getStringArgument($input, 'username');
        $users = $this->apiClient->getDatabaseUsers($database['id']);

        if ($users->isEmpty()) {
            throw new InvalidArgumentException(sprintf('The "%s" database server doesn\'t have any managed database users', $database['name']));
        } elseif (empty($username) && $input->isInteractive()) {
            $username = (string) $output->choice('What database user would you like to delete', $users->pluck('username')->all());
        }

        $user = $users->firstWhere('username', $username);

        if (empty($user['id'])) {
            throw new InvalidArgumentException(sprintf('No database user found with the "%s" username', $username));
        }

        if (!$output->confirm('Are you sure you want to delete this database user?', false)) {
            return;
        }

        $this->apiClient->deleteDatabaseUser($database['id'], $user['id']);

        $output->info('Database user deleted');
    }
}
