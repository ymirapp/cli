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
use Ymir\Cli\Command\Network\RemoveNatGatewayCommand;

class DeleteDatabaseServerCommand extends AbstractDatabaseServerCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to delete');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" database server?', $databaseServer['name']), false)) {
            return;
        }

        $this->apiClient->deleteDatabaseServer($databaseServer['id']);

        $this->output->infoWithDelayWarning('Database server deleted');

        if (!$databaseServer['publicly_accessible']) {
            $this->output->newLine();
            $this->output->note(sprintf('If you have no other resources using the private subnet, you should remove the network\'s NAT gateway using the "<comment>%s</comment>" command', RemoveNatGatewayCommand::NAME));
        }
    }
}
