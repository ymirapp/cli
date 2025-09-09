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

use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Exception\ApiRuntimeException;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;

class RotateDatabaseUserPasswordCommand extends AbstractCommand
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
            ->addArgument('user', InputArgument::OPTIONAL, 'The username of the database user to rotate the password of')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database user is located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to rotate the password of a database user on?');
        $databaseUser = $this->resolve(DatabaseUser::class, 'Which <comment>%s</comment> database user would you like to rotate the password of?', $databaseServer);

        if (!$this->output->warningConfirmation(sprintf('All projects that use the "<comment>%s</comment>" database server with the "<comment>%s</comment>" user will be unable to connect to the database server until they\'re redeployed.', $databaseServer->getName(), $databaseUser->getName()))) {
            return;
        }

        $newCredentials = $this->apiClient->rotateDatabaseUserPassword($databaseUser);

        if (!Arr::has($newCredentials, ['username', 'password'])) {
            throw new ApiRuntimeException('The API failed to return a new password');
        }

        $this->output->horizontalTable(
            ['Username', 'Password'],
            [[$newCredentials['username'], $newCredentials['password']]]
        );

        $this->output->info('Database user password rotated successfully');

        if (!$databaseServer->isPublic()) {
            $this->output->newLine();
            $this->output->important(sprintf('The password of the "<comment>%s</comment>" database user needs to be manually changed on the "<comment>%s</comment>" database server because it isn\'t publicly accessible. You can use the following query to change it:', $newCredentials['username'], $databaseServer->getName()));
            $this->output->writeln(sprintf('ALTER USER %s@\'%%\' IDENTIFIED BY %s', $newCredentials['username'], $newCredentials['password']));
        }

        $this->output->newLine();
        $this->output->important(sprintf('You need to redeploy all projects using this database user using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
