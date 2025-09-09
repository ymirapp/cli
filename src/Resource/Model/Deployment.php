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

final class Deployment extends AbstractResourceModel
{
    /**
     * The hash of the assets for the deployment.
     *
     * @var string|null
     */
    private $assetsHash;

    /**
     * The configuration of the deployment.
     *
     * @var array
     */
    private $configuration;

    /**
     * The date and time when the deployment was created.
     *
     * @var string
     */
    private $createdAt;

    /**
     * The failed message of the deployment.
     *
     * @var string|null
     */
    private $failedMessage;

    /**
     * The user that initiated the deployment.
     *
     * @var User|null
     */
    private $initiator;

    /**
     * The status of the deployment.
     *
     * @var string
     */
    private $status;

    /**
     * The steps of the deployment.
     *
     * @var array
     */
    private $steps;

    /**
     * The type of deployment.
     *
     * @var string
     */
    private $type;

    /**
     * The list of unmanaged domains for the deployment.
     *
     * @var array
     */
    private $unmanagedDomains;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $uuid, string $status, string $createdAt, array $configuration, string $type, array $unmanagedDomains, array $steps = [], ?User $initiator = null, ?string $failedMessage = null, ?string $assetsHash = null)
    {
        parent::__construct($id, $uuid);

        $this->assetsHash = $assetsHash;
        $this->configuration = $configuration;
        $this->createdAt = $createdAt;
        $this->failedMessage = $failedMessage;
        $this->initiator = $initiator;
        $this->status = $status;
        $this->steps = $steps;
        $this->type = $type;
        $this->unmanagedDomains = $unmanagedDomains;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'uuid', 'status', 'created_at', 'configuration', 'unmanaged_domains', 'type'])) {
            throw new InvalidArgumentException('Unable to create a deployment using the given array data');
        }

        $initiator = Arr::get($data, 'initiator');

        return new self(
            (int) $data['id'],
            (string) $data['uuid'],
            (string) $data['status'],
            (string) $data['created_at'],
            (array) $data['configuration'],
            (string) $data['type'],
            (array) $data['unmanaged_domains'],
            (array) Arr::get($data, 'steps', []),
            !empty($initiator) ? User::fromArray($initiator) : null,
            (string) Arr::get($data, 'failed_message'),
            (string) Arr::get($data, 'assets_hash')
        );
    }

    /**
     * Get the hash of the assets for the deployment.
     */
    public function getAssetsHash(): ?string
    {
        return $this->assetsHash;
    }

    /**
     * Get the configuration of the deployment.
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Get the date and time when the deployment was created.
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Get the failed message of the deployment.
     */
    public function getFailedMessage(): ?string
    {
        return $this->failedMessage;
    }

    /**
     * Get the user that initiated the deployment.
     */
    public function getInitiator(): ?User
    {
        return $this->initiator;
    }

    /**
     * Get the status of the deployment.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the steps of the deployment.
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get the type of deployment.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the list of unmanaged domains for the deployment.
     */
    public function getUnmanagedDomains(): array
    {
        return $this->unmanagedDomains;
    }

    /**
     * Get the UUID of the deployment.
     */
    public function getUuid(): string
    {
        return $this->getName();
    }
}
