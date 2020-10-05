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
use Ymir\Cli\Console\ConsoleOutput;

class DeleteDatabaseCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a database on a database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server where the database will be deleted')
            ->addArgument('name', InputArgument::OPTIONAL, 'The username of the new database user');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $databaseId = $this->determineDatabaseServer('On which database server would you like to delete a database?', $input, $output);
        $name = $this->getStringArgument($input, 'name');

        if (empty($name) && $input->isInteractive()) {
            $name = (string) $output->choice('Which database would you like to delete', $this->apiClient->getDatabases($databaseId)->filter(function (string $name) {
                return !in_array($name, ['information_schema', 'innodb', 'mysql', 'performance_schema', 'sys']);
            })->values()->all());
        }

        if (!$output->confirm('Are you sure you want to delete this database?', false)) {
            return;
        }

        $this->apiClient->deleteDatabase($databaseId, $name);

        $output->info('Database deleted');
    }
}
