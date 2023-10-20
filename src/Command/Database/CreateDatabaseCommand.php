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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class CreateDatabaseCommand extends AbstractDatabaseCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new database on a public database server')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the new database')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database will be created');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $databaseServer = $this->determineDatabaseServer('On which database server would you like to create the new database?', $input, $output);

        if (!$databaseServer['publicly_accessible']) {
            throw new RuntimeException('Database on private database servers need to be manually created.');
        }

        $name = $input->getStringArgument('name');

        if (empty($name)) {
            $name = $output->ask('What is the name of the database');
        }

        $this->apiClient->createDatabase($databaseServer['id'], $name);

        $output->info('Database created');
    }
}
