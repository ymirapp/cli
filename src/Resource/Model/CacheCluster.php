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

final class CacheCluster extends AbstractRegionalResourceModel
{
    /**
     * The endpoint used to access the cache cluster.
     *
     * @var string
     */
    private $endpoint;

    /**
     * The engine used by the cache cluster.
     *
     * @var string
     */
    private $engine;

    /**
     * The network that the cache cluster is on.
     *
     * @var Network
     */
    private $network;

    /**
     * The status of the cache cluster.
     *
     * @var string
     */
    private $status;

    /**
     * The cache cluster type.
     *
     * @var string
     */
    private $type;

    /**
     * Constructor.
     */
    private function __construct(int $id, string $name, string $region, string $status, string $endpoint, string $engine, string $type, Network $network)
    {
        parent::__construct($id, $name, $region);

        $this->endpoint = $endpoint;
        $this->engine = $engine;
        $this->network = $network;
        $this->status = $status;
        $this->type = $type;
    }

    /**
     * Create a cache cluster from the given array.
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'region', 'status', 'endpoint', 'engine', 'type', 'network'])) {
            throw new InvalidArgumentException('Unable to create a cache cluster using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            (string) $data['status'],
            (string) $data['endpoint'],
            (string) $data['engine'],
            (string) $data['type'],
            Network::fromArray((array) $data['network'])
        );
    }

    /**
     * Get the endpoint used to access the cache cluster.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Get the engine used by the cache cluster.
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Get the network that the cache cluster is on.
     */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * Get the status of the cache cluster.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the cache cluster type.
     */
    public function getType(): string
    {
        return $this->type;
    }
}
