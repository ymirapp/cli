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
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;

class ListDatabasesCommand extends AbstractCommand
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
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to list databases from?');

        if (!$databaseServer->isPublic()) {
            throw new ResourceStateException('Cannot list databases on a private database server');
        }

        $this->output->table(
            ['Name'],
            $this->apiClient->getDatabases($databaseServer)->map(function (Database $database) {
                return [$database->getName()];
            })->all()
        );
    }
}
