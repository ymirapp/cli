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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class ListDatabaseUsersCommand extends AbstractDatabaseCommand
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
            ->setDescription('List all the managed users on a database server')
            ->addArgument('database', InputArgument::OPTIONAL, 'The ID or name of the database server to list users from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $output->table(
            ['Id', 'Username', 'Created At'],
            $this->apiClient->getDatabaseUsers($this->determineDatabaseServer('Which database server would you like to list users from', $input, $output))->map(function (array $database) {
                return [$database['id'], $database['username'], Carbon::parse($database['created_at'])->diffForHumans()];
            })->all()
        );
    }
}
