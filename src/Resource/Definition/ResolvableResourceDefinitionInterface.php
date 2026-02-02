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

interface ResolvableResourceDefinitionInterface extends ResourceDefinitionInterface
{
    /**
     * Resolves an existing resource using the command-line input.
     *
     * If unable to resolve with the command-line input, the method will ask the user to select a resource with the
     * given question.
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []);
}
