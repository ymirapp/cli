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

use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\Resource\Model\DatabaseServer;

abstract class AbstractDatabaseServerRequirement extends AbstractRequirement
{
    /**
     * The Aurora database types mapped by database server engine.
     *
     * @var array
     */
    private const AURORA_TYPES = [
        DatabaseServer::ENGINE_MYSQL => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
        DatabaseServer::ENGINE_POSTGRESQL => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
    ];

    /**
     * Constructor.
     */
    public function __construct(string $question = '')
    {
        parent::__construct($question);
    }

    /**
     * Get the Aurora database type for the given database engine.
     */
    protected function getAuroraDatabaseTypeForEngine(string $engine): string
    {
        if (!isset(self::AURORA_TYPES[$engine])) {
            throw new UnsupportedDatabaseServerEngineException($engine);
        }

        return self::AURORA_TYPES[$engine];
    }

    /**
     * Check if the database server type is an Aurora database type.
     */
    protected function isAuroraDatabaseType(string $type): bool
    {
        return DatabaseServer::isAuroraType($type);
    }

    /**
     * Check if the Aurora database type is compatible with the database server engine.
     */
    protected function isAuroraDatabaseTypeCompatibleWithEngine(string $type, string $engine): bool
    {
        return $this->isAuroraDatabaseType($type) && $this->getAuroraDatabaseTypeForEngine($engine) === $type;
    }
}
