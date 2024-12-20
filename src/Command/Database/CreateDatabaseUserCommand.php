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
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new database user')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the user will be created');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databases = [];
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to create the new database user?');
        $username = $this->input->getStringArgument('username');

        if (empty($username)) {
            $username = $this->output->ask('What is the username of the new database user');
        }

        if ($databaseServer['publicly_accessible'] && !$this->output->confirm(sprintf('Do you want the "<comment>%s</comment>" user to have access to all databases?', $username), false)) {
            $databases = $this->output->multichoice('Please enter the comma-separated list of databases that you want the user to have access to', $this->apiClient->getDatabases($databaseServer['id']));
        }

        $user = $this->apiClient->createDatabaseUser($databaseServer['id'], $username, $databases);

        $this->output->horizontalTable(
            ['Username', 'Password'],
            [[$user['username'], $user['password']]]
        );

        $this->output->important(sprintf('Please write down the password shown below as it won\'t be displayed again. Ymir will inject it automatically whenever you assign this database user to a project. If you lose the password, use the "<comment>%s</comment>" command to generate a new one.', RotateDatabaseUserPasswordCommand::NAME));
        $this->output->newLine();
        $this->output->info('Database user created successfully');

        if (!$databaseServer['publicly_accessible']) {
            $this->output->newLine();
            $this->output->important(sprintf('The "<comment>%s</comment>" database user needs to be manually created on the "<comment>%s</comment>" database server because it isn\'t publicly accessible. You can use the following queries to create it and grant it access to the server:', $user['username'], $databaseServer['name']));
            $this->output->writeln(sprintf('CREATE USER %s@\'%%\' IDENTIFIED BY \'%s\'', $user['username'], $user['password']));
            $this->output->writeln(sprintf('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES ON *.* TO %s@\'%%\'', $user['username']));
        }
    }
}
