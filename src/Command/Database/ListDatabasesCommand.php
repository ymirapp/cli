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

class ListDatabasesCommand extends AbstractDatabaseServerCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all the databases on a public database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to list databases from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to list databases from');

        if (!$databaseServer['publicly_accessible']) {
            throw new RuntimeException('Database on private database servers cannot be listed.');
        }

        $this->output->table(
            ['Name'],
            $this->apiClient->getDatabases($databaseServer['id'])->map(function (string $name) {
                return [$name];
            })->all()
        );
    }
}
