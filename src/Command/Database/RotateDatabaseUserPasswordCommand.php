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
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class RotateDatabaseUserPasswordCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:user:rotate-password';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Rotate the password of a user on a database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server where the database user is located')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the database user to rotate the password of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to rotate the password of a database user?', $input, $output);
        $username = $this->getStringArgument($input, 'username');
        $users = $this->apiClient->getDatabaseUsers($databaseServer['id']);

        if ($users->isEmpty()) {
            throw new InvalidArgumentException('The database server doesn\'t have any managed database users');
        } elseif (empty($username)) {
            $username = (string) $output->choice('Which database user would you like to rotate the password of', $users->pluck('username'));
        }

        $databaseUser = $users->firstWhere('username', $username);

        if (empty($databaseUser['id'])) {
            throw new InvalidArgumentException(sprintf('No database user found with the "%s" username', $username));
        }

        $output->warning(sprintf('All projects that use the "<comment>%s</comment>" database server with the "<comment>%s</comment>" user will be unable to connect to the database server until they\'re redeployed.', $databaseServer['name'], $databaseUser['username']));

        if (!$output->confirm('Do you want to proceed?', false)) {
            return;
        }

        $newCredentials = $this->apiClient->rotateDatabaseUserPassword($databaseServer['id'], $databaseUser['id']);

        $output->horizontalTable(
            ['Username', 'Password'],
            [[$newCredentials['username'], $newCredentials['password']]]
        );

        $output->info('Database user password rotated successfully');

        if (!$databaseServer['publicly_accessible']) {
            $output->newLine();
            $output->important(sprintf('The password of the "%s" database user needs to be manually changed on the "%s" database server because it isn\'t publicly accessible. You can use the following query to change it:', $newCredentials['username'], $databaseServer['name']));
            $output->writeln(sprintf('ALTER USER %s@\'%%\' IDENTIFIED BY %s', $newCredentials['username'], $newCredentials['password']));
        }

        $output->newLine();
        $output->important(sprintf('You need to redeploy all projects using this database user using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
