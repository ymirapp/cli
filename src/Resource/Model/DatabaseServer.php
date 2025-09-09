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

final class DatabaseServer extends AbstractRegionalResourceModel
{
    /**
     * The name of the Aurora database type.
     *
     * @var string
     */
    public const AURORA_DATABASE_TYPE = 'aurora-mysql';

    /**
     * The endpoint used to access the database server.
     *
     * @var string|null
     */
    private $endpoint;

    /**
     * Flag whether the database server is locked or not.
     *
     * @var bool
     */
    private $locked;

    /**
     * The network that the database server is on.
     *
     * @var Network
     */
    private $network;

    /**
     * The master password of the database server.
     *
     * @var string|null
     */
    private $password;

    /**
     * The cloud provider where the database server resides.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * Flag whether the database server is publicly accessible.
     *
     * @var bool
     */
    private $public;

    /**
     * The status of the database server.
     *
     * @var string
     */
    private $status;

    /**
     * The maximum amount storage (in GB) that the database server can have.
     *
     * @var int|null
     */
    private $storage;

    /**
     * The database server type.
     *
     * @var string
     */
    private $type;

    /**
     * The master username of the database server.
     *
     * @var string|null
     */
    private $username;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $region, ?string $endpoint, bool $locked, Network $network, CloudProvider $provider, bool $public, string $status, ?int $storage, string $type, ?string $username = null, ?string $password = null)
    {
        parent::__construct($id, $name, $region);

        $this->endpoint = $endpoint;
        $this->locked = $locked;
        $this->network = $network;
        $this->password = $password;
        $this->provider = $provider;
        $this->public = $public;
        $this->status = $status;
        $this->storage = $storage;
        $this->type = $type;
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'endpoint', 'locked', 'network', 'provider', 'publicly_accessible', 'region', 'status', 'type'])) {
            throw new InvalidArgumentException('Unable to create a database server using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            isset($data['endpoint']) ? (string) $data['endpoint'] : null,
            (bool) $data['locked'],
            Network::fromArray((array) $data['network']),
            CloudProvider::fromArray((array) $data['provider']),
            (bool) $data['publicly_accessible'],
            (string) $data['status'],
            isset($data['storage']) ? (int) $data['storage'] : null,
            (string) $data['type'],
            isset($data['username']) ? (string) $data['username'] : null,
            isset($data['password']) ? (string) $data['password'] : null
        );
    }

    /**
     * Get the endpoint used to access the database server.
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Get the network that the database server is on.
     */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * Get the master password of the database server.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Get the cloud provider where the database server resides.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }

    /**
     * Get the status of the database server.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the maximum amount storage (in GB) that the database server can have.
     */
    public function getStorage(): ?int
    {
        return $this->storage;
    }

    /**
     * Get the database server type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the master username of the database server.
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Check if the database server is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Check if the database server is publicly accessible.
     */
    public function isPublic(): bool
    {
        return $this->public;
    }
}
