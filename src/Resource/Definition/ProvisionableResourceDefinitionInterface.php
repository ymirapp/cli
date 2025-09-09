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

namespace Ymir\Cli\Resource\Definition;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

interface ProvisionableResourceDefinitionInterface extends ResourceDefinitionInterface
{
    /**
     * Get the requirements for provisioning a resource.
     */
    public function getRequirements(): array;

    /**
     * Provision a new resource using the given fulfilled requirements.
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface;
}
