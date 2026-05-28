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

use Ymir\Cli\Resource\Model\DatabaseServer;

abstract class AbstractDatabaseServerRequirement extends AbstractRequirement
{
    /**
     * Constructor.
     */
    public function __construct(string $question = '')
    {
        parent::__construct($question);
    }

    /**
     * Check if the database server type is an Aurora database type.
     */
    protected function isAuroraDatabaseType(string $type): bool
    {
        return DatabaseServer::isAuroraType($type);
    }
}
