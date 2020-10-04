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
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $database = $this->determineDatabaseServer('Which', $input, $output);

        $output->horizontalTable(
            ['Id', 'Name', 'Status', new TableSeparator(), 'Network', 'Provider', 'Region'],
            [[$database['id'], $database['name'], $database['status'], new TableSeparator(),  $database['network']['name'], $database['network']['provider']['name'], $database['region']]]
        );
    }
}
