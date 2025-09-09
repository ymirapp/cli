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

namespace Ymir\Cli\Resource\Requirement;

use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContext;

class CacheClusterEngineRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        $engine = $context->getInput()->getStringOption('engine');

        if (!in_array($engine, ['redis', 'valkey'])) {
            throw new InvalidInputException('The cache cluster engine must be either "redis" or "valkey"');
        }

        return $engine;
    }
}
