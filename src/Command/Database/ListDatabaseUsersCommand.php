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
use Ymir\Cli\Resource\Model\DatabaseUser;

class ListDatabaseUsersCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:user:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all the managed users on a public database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to list users from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to list users from?');

        $this->output->table(
            ['Id', 'Username', 'Created At'],
            $this->apiClient->getDatabaseUsers($databaseServer)->map(function (DatabaseUser $databaseUser) {
                return [$databaseUser->getId(), $databaseUser->getName(), $databaseUser->getCreatedAt()->diffForHumans()];
            })->all()
        );
    }
}
