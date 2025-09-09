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

final class Network extends AbstractRegionalResourceModel
{
    /**
     * The bastion host used to connect to the private subnet of a network.
     *
     * @var BastionHost|null
     */
    private $bastionHost;

    /**
     * Flag whether the network has a nat gateway or not.
     *
     * @var bool
     */
    private $hasNatGateway;

    /**
     * The cloud provider where the network resides.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * The status of the network.
     *
     * @var string
     */
    private $status;

    /**
     * Constructor.
     */
    private function __construct(int $id, string $name, string $region, string $status, bool $hasNatGateway, CloudProvider $provider, ?BastionHost $bastionHost = null)
    {
        parent::__construct($id, $name, $region);

        $this->bastionHost = $bastionHost;
        $this->hasNatGateway = $hasNatGateway;
        $this->provider = $provider;
        $this->status = $status;
    }

    /**
     * Create a network from the given array.
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'region', 'status', 'provider'])) {
            throw new InvalidArgumentException('Unable to create a network using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            (string) $data['status'],
            (bool) Arr::get($data, 'has_nat_gateway', false),
            CloudProvider::fromArray((array) $data['provider']),
            !empty($data['bastion_host']) ? BastionHost::fromArray((array) $data['bastion_host']) : null
        );
    }

    /**
     * Get the bastion host used to connect to the private subnet of a network.
     */
    public function getBastionHost(): ?BastionHost
    {
        return $this->bastionHost;
    }

    /**
     * Get the cloud provider where the network resides.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }

    /**
     * Get the status of the network.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Checks whether the network has a nat gateway or not.
     */
    public function hasNatGateway(): bool
    {
        return $this->hasNatGateway;
    }
}
