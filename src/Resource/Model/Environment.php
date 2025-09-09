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

final class Environment extends AbstractResourceModel
{
    /**
     * The content delivery network configuration.
     *
     * @var array
     */
    private $contentDeliveryNetwork;

    /**
     * The gateway configuration.
     *
     * @var array
     */
    private $gateway;

    /**
     * The public store domain name.
     *
     * @var string
     */
    private $publicStoreDomainName;

    /**
     * The vanity domain name.
     *
     * @var string
     */
    private $vanityDomainName;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $vanityDomainName, array $gateway = [], array $contentDeliveryNetwork = [], string $publicStoreDomainName = '')
    {
        parent::__construct($id, $name);

        $this->contentDeliveryNetwork = $contentDeliveryNetwork;
        $this->gateway = $gateway;
        $this->publicStoreDomainName = $publicStoreDomainName;
        $this->vanityDomainName = $vanityDomainName;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['name', 'vanity_domain_name'])) {
            throw new InvalidArgumentException('Unable to create an environment using the given array data');
        }

        return new self(
            (int) Arr::get($data, 'id', 0),
            $data['name'],
            $data['vanity_domain_name'],
            (array) Arr::get($data, 'gateway', []),
            (array) Arr::get($data, 'content_delivery_network', []),
            (string) Arr::get($data, 'public_store_domain_name', '')
        );
    }

    /**
     * Get the content delivery network configuration.
     */
    public function getContentDeliveryNetwork(): array
    {
        return $this->contentDeliveryNetwork;
    }

    /**
     * Get the gateway configuration.
     */
    public function getGateway(): array
    {
        return $this->gateway;
    }

    /**
     * Get the public store domain name.
     */
    public function getPublicStoreDomainName(): string
    {
        return $this->publicStoreDomainName;
    }

    /**
     * Get the vanity domain name.
     */
    public function getVanityDomainName(): string
    {
        return $this->vanityDomainName;
    }
}
