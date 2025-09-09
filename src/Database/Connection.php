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

namespace Ymir\Cli\Database;

use Ymir\Cli\Resource\Model\DatabaseServer;

class Connection
{
    /**
     * The database the connection is for.
     *
     * @var string
     */
    private $database;

    /**
     * The database server the connection is for.
     *
     * @var DatabaseServer
     */
    private $databaseServer;

    /**
     * The password the connection is for.
     *
     * @var string
     */
    private $password;

    /**
     * The user the connection is for.
     *
     * @var string
     */
    private $user;

    /**
     * Constructor.
     */
    public function __construct(string $database, DatabaseServer $databaseServer, string $user, string $password)
    {
        $this->database = $database;
        $this->databaseServer = $databaseServer;
        $this->user = $user;
        $this->password = $password;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getDatabaseServer(): DatabaseServer
    {
        return $this->databaseServer;
    }

    public function getDsn(): string
    {
        return sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $this->getHost(), $this->getPort(), $this->getDatabase());
    }

    public function getHost(): string
    {
        return $this->databaseServer->isPublic() ? $this->databaseServer->getEndpoint() : '127.0.0.1';
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPort(): string
    {
        return $this->databaseServer->isPublic() ? '3306' : '3305';
    }

    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Checks if the connection needs an SSH tunnel to connect to the database server.
     */
    public function needsSshTunnel(): bool
    {
        return !$this->databaseServer->isPublic();
    }
}
