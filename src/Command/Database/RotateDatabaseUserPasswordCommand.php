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
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Exception\InvalidInputException;

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
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the database user to rotate the password of')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database user is located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to rotate the password of a database user?');
        $username = $this->input->getStringArgument('username');
        $users = $this->apiClient->getDatabaseUsers($databaseServer['id']);

        if ($users->isEmpty()) {
            throw new RuntimeException('The database server doesn\'t have any managed database users');
        } elseif (empty($username)) {
            $username = (string) $this->output->choice('Which database user would you like to rotate the password of', $users->pluck('username'));
        }

        $databaseUser = $users->firstWhere('username', $username);

        if (empty($databaseUser['id'])) {
            throw new InvalidInputException(sprintf('No database user found with the "%s" username', $username));
        }

        $this->output->warning(sprintf('All projects that use the "<comment>%s</comment>" database server with the "<comment>%s</comment>" user will be unable to connect to the database server until they\'re redeployed.', $databaseServer['name'], $databaseUser['username']));

        if (!$this->output->confirm('Do you want to proceed?', false)) {
            return;
        }

        $newCredentials = $this->apiClient->rotateDatabaseUserPassword($databaseServer['id'], $databaseUser['id']);

        $this->output->horizontalTable(
            ['Username', 'Password'],
            [[$newCredentials['username'], $newCredentials['password']]]
        );

        $this->output->info('Database user password rotated successfully');

        if (!$databaseServer['publicly_accessible']) {
            $this->output->newLine();
            $this->output->important(sprintf('The password of the "<comment>%s</comment>" database user needs to be manually changed on the "<comment>%s</comment>" database server because it isn\'t publicly accessible. You can use the following query to change it:', $newCredentials['username'], $databaseServer['name']));
            $this->output->writeln(sprintf('ALTER USER %s@\'%%\' IDENTIFIED BY %s', $newCredentials['username'], $newCredentials['password']));
        }

        $this->output->newLine();
        $this->output->important(sprintf('You need to redeploy all projects using this database user using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
