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

class GetDatabaseServerInfoCommand extends AbstractDatabaseServerCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information on a database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to get information about');

        $this->output->horizontalTable(
            ['Id', 'Name', 'Status', 'Locked', 'Public', new TableSeparator(), 'Provider', 'Network', 'Region', 'Type', 'Storage', 'Endpoint'],
            [[
                $databaseServer['id'],
                $databaseServer['name'],
                $this->output->formatStatus($databaseServer['status']),
                $this->output->formatBoolean($databaseServer['locked']),
                $this->output->formatBoolean($databaseServer['publicly_accessible']),
                new TableSeparator(),
                $databaseServer['network']['provider']['name'],
                $databaseServer['network']['name'],
                $databaseServer['region'],
                $databaseServer['type'],
                $databaseServer['storage'] ? $databaseServer['storage'].'GB' : 'N/A',
                $databaseServer['endpoint'] ?? 'pending',
            ]]
        );
    }
}
