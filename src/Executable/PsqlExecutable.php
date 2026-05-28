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

namespace Ymir\Cli\Executable;

use Ymir\Cli\Database\Connection;
use Ymir\Cli\Process\Process;

class PsqlExecutable extends AbstractExecutable
{
    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'psql';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        return 'psql';
    }

    /**
     * Import a PostgreSQL database from a SQL backup stream.
     *
     * @param resource $input
     */
    public function import(Connection $connection, $input): Process
    {
        return $this->runWithArguments(['--no-psqlrc', '--set=ON_ERROR_STOP=1', sprintf('--host=%s', $connection->getHost()), sprintf('--port=%s', $connection->getPort()), sprintf('--username=%s', $connection->getUser()), '--no-password', sprintf('--dbname=%s', $connection->getDatabase()), '--file=-'], null, ['PGPASSWORD' => $connection->getPassword()], $input, null);
    }
}
