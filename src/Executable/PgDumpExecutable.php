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

class PgDumpExecutable extends AbstractExecutable
{
    /**
     * Export a PostgreSQL database to a SQL file.
     */
    public function dump(Connection $connection, string $filename, string $compression = 'none'): Process
    {
        $arguments = ['--clean', '--format=plain', '--if-exists', '--no-acl', '--no-owner', '--no-password', sprintf('--host=%s', $connection->getHost()), sprintf('--port=%s', $connection->getPort()), sprintf('--username=%s', $connection->getUser()), sprintf('--file=%s', $filename), sprintf('--dbname=%s', $connection->getDatabase())];

        if ('gzip' === $compression) {
            $arguments[] = '--compress=9';
        }

        return $this->runWithArguments($arguments, null, ['PGPASSWORD' => $connection->getPassword()], null, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'pg_dump';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        return 'pg_dump';
    }
}
