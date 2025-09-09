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

namespace Ymir\Cli\Resource\Model;

use Carbon\Carbon;
use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Support\Arr;

final class DatabaseUser extends AbstractResourceModel
{
    /**
     * The "created_at" timestamp.
     *
     * @var Carbon
     */
    private $createdAt;

    /**
     * The database server that this user belongs to.
     *
     * @var DatabaseServer
     */
    private $databaseServer;

    /**
     * The password of the database user.
     *
     * @var string|null
     */
    private $password;

    /**
     * Constructor.
     */
    public function __construct(int $id, Carbon $createdAt, DatabaseServer $databaseServer, string $name, ?string $password = null)
    {
        parent::__construct($id, $name);

        $this->createdAt = $createdAt;
        $this->databaseServer = $databaseServer;
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'database_server', 'username', 'created_at'])) {
            throw new InvalidArgumentException('Unable to create a database user using the given array data');
        }

        return new self(
            (int) $data['id'],
            Carbon::parse((string) $data['created_at']),
            DatabaseServer::fromArray((array) $data['database_server']),
            (string) $data['username'],
            !empty($data['password']) && is_string($data['password']) ? $data['password'] : null
        );
    }

    /**
     * Get the "created_at" timestamp.
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the database server that this user belongs to.
     */
    public function getDatabaseServer(): DatabaseServer
    {
        return $this->databaseServer;
    }

    /**
     * Get the password of the database user.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
}
