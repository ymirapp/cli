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
use Ymir\Cli\Exception\Resource\ProvisioningFailedException;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;

class CreateDatabaseUserCommand extends AbstractCommand
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
            ->addArgument('user', InputArgument::OPTIONAL, 'The username of the new database user')
            ->addArgument('databases', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The databases the user will have access to')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the user will be created');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like the database user to be created on?');
        $databaseUser = $this->provision(DatabaseUser::class, [], $databaseServer);

        if (!$databaseUser instanceof DatabaseUser) {
            throw new ProvisioningFailedException('Failed to provision database user');
        }

        $this->output->horizontalTable(
            ['Username', 'Password'],
            [[$databaseUser->getName(), $databaseUser->getPassword()]]
        );

        $this->output->important(sprintf('Please write down the password shown below as it won\'t be displayed again. Ymir will inject it automatically whenever you assign this database user to a project. If you lose the password, use the "<comment>%s</comment>" command to generate a new one.', RotateDatabaseUserPasswordCommand::NAME));
        $this->output->newLine();
        $this->output->info('Database user created successfully');

        if (!$databaseUser->getDatabaseServer()->isPublic()) {
            $this->output->newLine();
            $this->output->important(sprintf('The "<comment>%s</comment>" database user needs to be manually created on the "<comment>%s</comment>" database server because it isn\'t publicly accessible. You can use the following queries to create it and grant it access to the server:', $databaseUser->getName(), $databaseUser->getDatabaseServer()->getName()));
            $this->output->writeln(sprintf('CREATE USER %s@\'%%\' IDENTIFIED BY \'%s\'', $databaseUser->getName(), $databaseUser->getPassword()));
            $this->output->writeln(sprintf('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES ON *.* TO %s@\'%%\'', $databaseUser->getName()));
        }
    }
}
