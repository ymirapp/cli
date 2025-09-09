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

final class DnsZone extends AbstractResourceModel
{
    /**
     * The name servers for the DNS zone.
     *
     * @var array
     */
    private $nameServers;

    /**
     * The cloud provider associated with the DNS zone.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $domainName, CloudProvider $provider, array $nameServers = [])
    {
        parent::__construct($id, $domainName);

        $this->nameServers = $nameServers;
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['domain_name', 'id', 'provider'])) {
            throw new InvalidArgumentException('Unable to create a DNS zone using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['domain_name'],
            CloudProvider::fromArray((array) $data['provider']),
            (array) Arr::get($data, 'name_servers', [])
        );
    }

    /**
     * Get the name servers for the DNS zone.
     */
    public function getNameServers(): array
    {
        return $this->nameServers;
    }

    /**
     * Get the cloud provider associated with the DNS zone.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }
}
