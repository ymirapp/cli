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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Database;

class CreateDatabaseCommand extends AbstractCommand
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
            ->addArgument('database', InputArgument::OPTIONAL, 'The name of the new database')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'The ID or name of the database server where the database will be created');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->provision(Database::class);

        $this->output->info('Database created');
    }
}
