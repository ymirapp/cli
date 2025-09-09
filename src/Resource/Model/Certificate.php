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

final class Certificate extends AbstractRegionalResourceModel
{
    /**
     * The domains that the SSL certificate is for.
     *
     * @var array
     */
    private $domains;

    /**
     * Whether the SSL certificate is in use or not.
     *
     * @var bool
     */
    private $inUse;

    /**
     * The cloud provider where the SSL certificate resides.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * The status of the SSL certificate.
     *
     * @var string
     */
    private $status;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $region, CloudProvider $provider, string $status, bool $inUse, array $domains = [])
    {
        parent::__construct($id, $name, $region);

        $this->domains = $domains;
        $this->inUse = $inUse;
        $this->provider = $provider;
        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'provider', 'region', 'status'])) {
            throw new InvalidArgumentException('Unable to create an SSL certificate using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['id'],
            (string) $data['region'],
            CloudProvider::fromArray((array) $data['provider']),
            (string) $data['status'],
            (bool) Arr::get($data, 'in_use', false),
            (array) Arr::get($data, 'domains', [])
        );
    }

    /**
     * Get the domains that the SSL certificate is for.
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * Get the cloud provider where the SSL certificate resides.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }

    /**
     * Get the status of the SSL certificate.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the DNS records required to validate the SSL certificate.
     */
    public function getValidationRecords(): array
    {
        return collect($this->domains)
            ->where('managed', false)
            ->pluck('validation_record')
            ->filter()
            ->unique(function (array $validationRecord) {
                return $validationRecord['name'].$validationRecord['value'];
            })
            ->map(function (array $validationRecord) {
                return ['CNAME', $validationRecord['name'], $validationRecord['value']];
            })
            ->all();
    }

    /**
     * Whether the SSL certificate is in use or not.
     */
    public function isInUse(): bool
    {
        return $this->inUse;
    }
}
