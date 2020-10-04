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
use Ymir\Cli\Console\OutputStyle;

class ListDatabasesCommand extends AbstractDatabaseCommand
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
            ->setDescription('List all the databases on a database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to list users from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $database = $this->determineDatabaseServer('Which database server would you like to list databases from', $input, $output);

        $output->table(
            ['Name'],
            $this->apiClient->getDatabases($database['id'])->map(function (string $name) {
                return [$name];
            })->all()
        );
    }
}
