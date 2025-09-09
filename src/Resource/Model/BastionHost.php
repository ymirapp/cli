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

final class BastionHost extends AbstractResourceModel
{
    /**
     * The endpoint used to access the bastion host.
     *
     * @var string
     */
    private $endpoint;

    /**
     * The private RSA key used to access the bastion host.
     *
     * @var string
     */
    private $privateKey;

    /**
     * The status of the bastion host.
     *
     * @var string
     */
    private $status;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $endpoint, string $privateKey, string $status)
    {
        parent::__construct($id, $name);

        $this->endpoint = $endpoint;
        $this->privateKey = $privateKey;
        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'key_name', 'endpoint', 'private_key', 'status'])) {
            throw new InvalidArgumentException('Unable to create a bastion host using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['key_name'],
            (string) $data['endpoint'],
            (string) $data['private_key'],
            (string) $data['status']
        );
    }

    /**
     * Get the endpoint used to access the bastion host.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Get the private RSA key used to access the bastion host.
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Get the status of the bastion host.
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
