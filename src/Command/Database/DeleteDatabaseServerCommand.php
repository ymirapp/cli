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
use Ymir\Cli\Command\Network\RemoveNatGatewayCommand;
use Ymir\Cli\Console\OutputInterface;

class DeleteDatabaseServerCommand extends AbstractDatabaseCommand
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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to delete', $input, $output);

        if (!$output->confirm('Are you sure you want to delete this database server?', false)) {
            return;
        }

        $this->apiClient->deleteDatabaseServer($databaseServer['id']);

        $output->infoWithDelayWarning('Database server deleted');

        if (!$databaseServer['publicly_accessible']) {
            $output->newLine();
            $output->note(sprintf('If you have no other resources using the private subnet, you should remove the network\'s NAT gateway using the "%s" command', RemoveNatGatewayCommand::NAME));
        }
    }
}
