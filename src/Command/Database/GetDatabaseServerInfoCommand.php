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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class GetDatabaseServerInfoCommand extends AbstractDatabaseCommand
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
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to get information about', $input, $output);

        $output->horizontalTable(
            ['Id', 'Name', 'Status', 'Public', new TableSeparator(), 'Provider', 'Network', 'Region', 'Type', 'Storage', 'Endpoint'],
            [[
                $databaseServer['id'],
                $databaseServer['name'],
                $output->formatStatus($databaseServer['status']),
                $databaseServer['publicly_accessible'] ? 'yes' : 'no',
                new TableSeparator(),
                $databaseServer['network']['provider']['name'],
                $databaseServer['network']['name'],
                $databaseServer['region'],
                $databaseServer['type'],
                $databaseServer['storage'].'GB',
                $databaseServer['endpoint'],
            ]]
        );
    }
}
