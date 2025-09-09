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

namespace Ymir\Cli\Resource;

use Illuminate\Support\Collection;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

class ResourceCollection extends Collection
{
    /**
     * Find the first resource that matches the given ID.
     */
    public function firstWhereId(int $id): ?ResourceModelInterface
    {
        return $this->first(function (ResourceModelInterface $resource) use ($id) {
            return $id === $resource->getId();
        });
    }

    /**
     * Find the first resource that matches the given ID or name.
     */
    public function firstWhereIdOrName(string $idOrName): ?ResourceModelInterface
    {
        return $this->first(function (ResourceModelInterface $resource) use ($idOrName) {
            return $this->resourceModelMatchesIdOrName($resource, $idOrName);
        });
    }

    /**
     * Find the first resource that matches the given name.
     */
    public function firstWhereName(string $name): ?ResourceModelInterface
    {
        return $this->first(function (ResourceModelInterface $resource) use ($name) {
            return $name === $resource->getName();
        });
    }

    /**
     * Find all resources that match the given ID or name.
     */
    public function whereIdOrName(string $idOrName): self
    {
        return $this->filter(function (ResourceModelInterface $resource) use ($idOrName): bool {
            return $this->resourceModelMatchesIdOrName($resource, $idOrName);
        });
    }

    /**
     * Find all resources that match the given name.
     */
    public function whereName(string $name): self
    {
        return $this->filter(function (ResourceModelInterface $resource) use ($name): bool {
            return $name === $resource->getName();
        });
    }

    /**
     * Check if a resource matches the given ID or name.
     */
    private function resourceModelMatchesIdOrName(ResourceModelInterface $resource, string $idOrName): bool
    {
        return (is_numeric($idOrName) && (int) $idOrName === $resource->getId())
            || $idOrName === $resource->getName();
    }
}
