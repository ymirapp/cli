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

use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;

class ListDatabaseUsersCommand extends AbstractDatabaseServerCommand
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
        $this->output->table(
            ['Id', 'Username', 'Created At'],
            $this->apiClient->getDatabaseUsers($this->determineDatabaseServer('Which database server would you like to list users from')['id'])->map(function (array $database) {
                return [$database['id'], $database['username'], Carbon::parse($database['created_at'])->diffForHumans()];
            })->all()
        );
    }
}
