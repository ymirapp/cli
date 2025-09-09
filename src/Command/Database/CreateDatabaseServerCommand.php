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

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\Resource\ProvisioningFailedException;
use Ymir\Cli\Resource\Model\DatabaseServer;

class CreateDatabaseServerCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new database server')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database server')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the database will be created')
            ->addOption('private', null, InputOption::VALUE_NONE, 'The created database server won\'t be publicly accessible')
            ->addOption('public', null, InputOption::VALUE_NONE, 'The created database server will be publicly accessible')
            ->addOption('serverless', null, InputOption::VALUE_NONE, 'Create an Aurora serverless database cluster (overrides all other options)')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database server')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database server type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->provision(DatabaseServer::class);

        if (!$databaseServer instanceof DatabaseServer) {
            throw new ProvisioningFailedException('Failed to provision database server');
        }

        $this->output->important(sprintf('Please write down the password shown below as it won\'t be displayed again. Ymir will inject it automatically whenever you assign this database server to a project. If you lose the password, use the "<comment>%s</comment>" command to generate a new one.', RotateDatabaseServerPasswordCommand::NAME));
        $this->output->newLine();

        $this->output->horizontalTable(
            ['Database Sever', new TableSeparator(), 'Username', 'Password', new TableSeparator(), 'Type', 'Public', 'Storage (in GB)'],
            [[$databaseServer->getName(), new TableSeparator(), $databaseServer->getUsername(), $databaseServer->getPassword(), new TableSeparator(), $databaseServer->getType(), $this->output->formatBoolean($databaseServer->isPublic()), $databaseServer->getStorage() ?? 'N/A']]
        );

        $this->output->infoWithDelayWarning('Database server created');
    }
}
