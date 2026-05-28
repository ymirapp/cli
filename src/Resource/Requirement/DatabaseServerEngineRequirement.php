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
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseServerEngineRequirement extends AbstractDatabaseServerRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        $engine = $context->getInput()->getStringOption('engine', true);

        if (null === $engine && $context->getInput()->isInteractive()) {
            $engine = $context->getOutput()->choice($this->question, DatabaseServer::getEngineLabels());
        }

        if (!is_string($engine) || !DatabaseServer::isEngine($engine)) {
            throw new InvalidInputException('The database server engine must be either "mysql" or "postgresql"');
        }

        return $engine;
    }
}
