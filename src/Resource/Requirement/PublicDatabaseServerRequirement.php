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
use Ymir\Cli\Resource\Model\ResourceModelInterface;

class PublicDatabaseServerRequirement extends DatabaseServerRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ResourceModelInterface
    {
        $databaseServer = parent::fulfill($context, $fulfilledRequirements);

        if ($databaseServer instanceof DatabaseServer && !$databaseServer->isPublic()) {
            throw new InvalidInputException('Please select a public database server');
        }

        return $databaseServer;
    }
}
