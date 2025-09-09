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

final class EmailIdentity extends AbstractRegionalResourceModel
{
    /**
     * The DKIM authentication records.
     *
     * @var array
     */
    private $dkimAuthenticationRecords;

    /**
     * Whether the email identity is managed by Ymir or not.
     *
     * @var bool
     */
    private $managed;

    /**
     * The cloud provider where the email identity resides.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * The type of the email identity.
     *
     * @var string
     */
    private $type;

    /**
     * Whether the email identity is verified or not.
     *
     * @var bool
     */
    private $verified;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $region, CloudProvider $provider, string $type, bool $verified, bool $managed, array $dkimAuthenticationRecords = [])
    {
        parent::__construct($id, $name, $region);

        $this->dkimAuthenticationRecords = $dkimAuthenticationRecords;
        $this->managed = $managed;
        $this->provider = $provider;
        $this->type = $type;
        $this->verified = $verified;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'provider', 'region', 'type', 'verified'])) {
            throw new InvalidArgumentException('Unable to create an email identity using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            CloudProvider::fromArray((array) $data['provider']),
            (string) $data['type'],
            (bool) $data['verified'],
            (bool) Arr::get($data, 'managed', false),
            (array) Arr::get($data, 'dkim_authentication_records', [])
        );
    }

    /**
     * Get the DKIM authentication records.
     */
    public function getDkimAuthenticationRecords(): array
    {
        return $this->dkimAuthenticationRecords;
    }

    /**
     * Get the cloud provider where the email identity resides.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }

    /**
     * Get the type of the email identity.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Whether the email identity is managed by Ymir or not.
     */
    public function isManaged(): bool
    {
        return $this->managed;
    }

    /**
     * Whether the email identity is verified or not.
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }
}
