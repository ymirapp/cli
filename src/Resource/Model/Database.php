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

use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Support\Arr;

final class Database implements ResourceModelInterface
{
    /**
     * The database server that this database belongs to.
     *
     * @var DatabaseServer
     */
    private $databaseServer;

    /**
     * The name of the database.
     *
     * @var string
     */
    private $name;

    /**
     * Constructor.
     */
    public function __construct(string $name, DatabaseServer $databaseServer)
    {
        $this->databaseServer = $databaseServer;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['name', 'database_server'])) {
            throw new InvalidArgumentException('Unable to create a database using the given array data');
        }

        return new self(
            (string) $data['name'],
            DatabaseServer::fromArray((array) $data['database_server'])
        );
    }

    /**
     * Get the database server that this database belongs to.
     */
    public function getDatabaseServer(): DatabaseServer
    {
        return $this->databaseServer;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
}
