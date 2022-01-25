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
use Ymir\Cli\Command\Project\DeployProjectCommand;
use Ymir\Cli\Command\Project\RedeployProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class RotateDatabaseServerPasswordCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:rotate-password';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Rotate the password of the database server\'s "ymir" user')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to rotate the password of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to rotate the password of?', $input, $output);

        $output->writeln(sprintf('<comment>Warning:</comment> All projects that use the "<comment>%s</comment>" database server with the default user will be unable to connect to the database server until they\'re redeployed.', $databaseServer['name']));

        if (!$output->confirm('Do you want to proceed?', false)) {
            return;
        }

        $newCredentials = $this->apiClient->rotateDatabaseServerPassword($databaseServer['id']);

        $output->horizontalTable(
            ['Username', 'Password'],
            [[$newCredentials['username'], $newCredentials['password']]]
        );

        $output->infoWithDelayWarning('Database server password rotated successfully');
        $output->newLine();
        $output->writeln(sprintf('<comment>Important:</comment> You need to redeploy all projects using this database server with the default user using either the "<comment>%s</comment>" or "<comment>%s</comment>" commands for the change to take effect.', DeployProjectCommand::ALIAS, RedeployProjectCommand::ALIAS));
    }
}
