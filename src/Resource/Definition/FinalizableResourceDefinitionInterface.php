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

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

interface FinalizableResourceDefinitionInterface extends ProvisionableResourceDefinitionInterface
{
    /**
     * Finalize the given provisioned resource.
     */
    public function finalize(ExecutionContext $context, ResourceModelInterface $resource, array $fulfilledRequirements): ResourceModelInterface;
}
