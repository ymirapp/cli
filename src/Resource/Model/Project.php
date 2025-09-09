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

final class Project extends AbstractRegionalResourceModel
{
    /**
     * The default project environments.
     */
    public const DEFAULT_ENVIRONMENTS = ['staging', 'production'];

    /**
     * The cloud provider where the project resides.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * The ECR repository URI where the project's docker images are stored.
     *
     * @var string|null
     */
    private $repositoryUri;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $region, CloudProvider $provider, ?string $repositoryUri = null)
    {
        parent::__construct($id, $name, $region);

        $this->provider = $provider;
        $this->repositoryUri = $repositoryUri;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'region', 'provider'])) {
            throw new InvalidArgumentException('Unable to create a project using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['region'],
            CloudProvider::fromArray((array) $data['provider']),
            Arr::get($data, 'repository_uri')
        );
    }

    /**
     * Get the cloud provider where the project resides.
     */
    public function getProvider(): CloudProvider
    {
        return $this->provider;
    }

    /**
     * Get the ECR repository URI where the project's docker images are stored.
     */
    public function getRepositoryUri(): ?string
    {
        return $this->repositoryUri;
    }

    /**
     * Get the team that the project belongs to.
     */
    public function getTeam(): Team
    {
        return $this->provider->getTeam();
    }
}
