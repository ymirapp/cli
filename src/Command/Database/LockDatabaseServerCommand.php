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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\DatabaseServer;

class LockDatabaseServerCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:lock';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Lock the database server which prevents it from being deleted')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to lock');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->apiClient->changeDatabaseServerLock($this->resolve(DatabaseServer::class, 'Which database server would you like to lock?'), true);

        $this->output->info('Database server locked');
    }
}
