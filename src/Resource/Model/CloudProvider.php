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

final class CloudProvider extends AbstractResourceModel
{
    /**
     * The authentication information of the cloud provider.
     *
     * @var array
     */
    private $authentication;

    /**
     * The status of the cloud provider.
     *
     * @var string
     */
    private $status;

    /**
     * The team that the cloud provider belongs to.
     *
     * @var Team
     */
    private $team;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, Team $team, string $status, array $authentication = [])
    {
        parent::__construct($id, $name);

        $this->authentication = $authentication;
        $this->status = $status;
        $this->team = $team;
    }

    /**
     * Create a cloud provider from the given array.
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'team', 'status'])) {
            throw new InvalidArgumentException('Unable to create a cloud provider using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            Team::fromArray((array) $data['team']),
            (string) $data['status'],
            (array) Arr::get($data, 'authentication', [])
        );
    }

    /**
     * Get the external ID for configuring AssumeRole authentication.
     */
    public function getAssumeRoleExternalId(): ?string
    {
        return Arr::get($this->authentication, 'assume_role.external_id');
    }

    /**
     * Get the required role name for configuring AssumeRole authentication.
     */
    public function getAssumeRoleRoleName(): ?string
    {
        return Arr::get($this->authentication, 'assume_role.role_name');
    }

    /**
     * Get the Ymir AWS account ID for configuring AssumeRole authentication.
     */
    public function getAssumeRoleYmirAccountId(): ?string
    {
        return Arr::get($this->authentication, 'assume_role.ymir_account_id');
    }

    /**
     * Get the authentication method of the cloud provider.
     */
    public function getAuthenticationMethod(): ?string
    {
        return Arr::get($this->authentication, 'method');
    }

    /**
     * Get the status of the cloud provider.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the team that the cloud provider belongs to.
     */
    public function getTeam(): Team
    {
        return $this->team;
    }
}
