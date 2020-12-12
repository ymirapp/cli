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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class CreateDatabaseUserCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:user:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new user on a database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server where the user will be created')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new database user');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $user = $this->retryApi(function () use ($input, $output) {
            $databaseId = $this->determineDatabaseServer('On which database server would you like to create the new database user?', $input, $output);
            $databases = [];
            $username = $this->getStringArgument($input, 'username');

            if (empty($username) && $input->isInteractive()) {
                $username = $output->ask('What is the username of the new database user');
            }

            if (!$output->confirm('Do you want to the new user to have access to all databases?', false)) {
                $databases = $output->multichoice('Please enter the comma-separated list of databases that you want the user to have access to', $this->apiClient->getDatabases($databaseId)->all());
            }

            return $this->apiClient->createDatabaseUser($databaseId, $username, $databases);
        }, 'Do you want to try creating a database user again?', $output);

        $output->horizontalTable(
            ['Username', 'Password'],
            [[$user['username'], $user['password']]]
        );

        $output->info('Database user created');
    }
}
