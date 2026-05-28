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
use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\Support\Arr;

final class DatabaseServer extends AbstractRegionalResourceModel
{
    /**
     * The name of the Aurora MySQL database type.
     *
     * @var string
     */
    public const AURORA_MYSQL_DATABASE_TYPE = 'aurora-mysql';

    /**
     * The name of the Aurora PostgreSQL database type.
     *
     * @var string
     */
    public const AURORA_POSTGRESQL_DATABASE_TYPE = 'aurora-postgresql';

    /**
     * The MySQL database server engine.
     *
     * @var string
     */
    public const ENGINE_MYSQL = 'mysql';

    /**
     * The PostgreSQL database server engine.
     *
     * @var string
     */
    public const ENGINE_POSTGRESQL = 'postgresql';

    /**
     * The Aurora database types.
     *
     * @var array
     */
    private const AURORA_DATABASE_TYPES = [
        self::AURORA_MYSQL_DATABASE_TYPE,
        self::AURORA_POSTGRESQL_DATABASE_TYPE,
    ];

    /**
     * The display labels for database server engines.
     *
     * @var array
     */
    private const ENGINE_LABELS = [
        self::ENGINE_MYSQL => 'MySQL',
        self::ENGINE_POSTGRESQL => 'PostgreSQL',
    ];

    /**
     * The default local SSH tunnel ports for database server engines.
     *
     * @var array
     */
    private const ENGINE_LOCAL_PORTS = [
        self::ENGINE_MYSQL => 3305,
        self::ENGINE_POSTGRESQL => 5433,
    ];

    /**
     * The default ports for database server engines.
     *
     * @var array
     */
    private const ENGINE_PORTS = [
        self::ENGINE_MYSQL => 3306,
        self::ENGINE_POSTGRESQL => 5432,
    ];

    /**
     * The database server engines.
     *
     * @var array
     */
    private const ENGINES = [
        self::ENGINE_MYSQL,
        self::ENGINE_POSTGRESQL,
    ];

    /**
     * The endpoint used to access the database server.
     *
     * @var string|null
     */
    private $endpoint;

    /**
     * The database server engine.
     *
     * @var string
     */
    private $engine;

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
    public function __construct(int $id, string $name, string $region, ?string $endpoint, string $engine, bool $locked, Network $network, CloudProvider $provider, bool $public, string $status, ?int $storage, string $type, ?string $username = null, ?string $password = null)
    {
        parent::__construct($id, $name, $region);

        $this->endpoint = $endpoint;
        $this->engine = $engine;
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
        if (!Arr::has($data, ['id', 'name', 'endpoint', 'engine', 'locked', 'network', 'provider', 'publicly_accessible', 'region', 'status', 'type'])) {
            throw new InvalidArgumentException('Unable to create a database server using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            isset($data['endpoint']) ? (string) $data['endpoint'] : null,
            (string) $data['engine'],
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
     * Get the display labels for database server engines.
     */
    public static function getEngineLabels(): array
    {
        return self::ENGINE_LABELS;
    }

    /**
     * Check if the database server type is an Aurora database type.
     */
    public static function isAuroraType(string $type): bool
    {
        return in_array($type, self::AURORA_DATABASE_TYPES, true);
    }

    /**
     * Check if the database server engine is valid.
     */
    public static function isEngine(string $engine): bool
    {
        return in_array($engine, self::ENGINES, true);
    }

    /**
     * Get the display label for the given database server engine.
     */
    private static function getEngineLabelForEngine(string $engine): string
    {
        if (!isset(self::ENGINE_LABELS[$engine])) {
            throw new UnsupportedDatabaseServerEngineException($engine);
        }

        return self::ENGINE_LABELS[$engine];
    }

    /**
     * Get the default local SSH tunnel port.
     */
    public function getDefaultLocalPort(): int
    {
        if (!isset(self::ENGINE_LOCAL_PORTS[$this->engine])) {
            throw new UnsupportedDatabaseServerEngineException($this->engine);
        }

        return self::ENGINE_LOCAL_PORTS[$this->engine];
    }

    /**
     * Get the default database server port.
     */
    public function getDefaultPort(): int
    {
        if (!isset(self::ENGINE_PORTS[$this->engine])) {
            throw new UnsupportedDatabaseServerEngineException($this->engine);
        }

        return self::ENGINE_PORTS[$this->engine];
    }

    /**
     * Get the endpoint used to access the database server.
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Get the database server engine.
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Get the database server engine label.
     */
    public function getEngineLabel(): string
    {
        return self::getEngineLabelForEngine($this->engine);
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
     * Check if the database server is an Aurora database server.
     */
    public function isAurora(): bool
    {
        return self::isAuroraType($this->type);
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
