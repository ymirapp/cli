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

namespace Ymir\Cli\Database;

use PDO as BasePDO;

class PDO extends BasePDO
{
    /**
     * Default PDO options.
     *
     * @var array
     */
    private const DEFAULT_OPTIONS = [
        self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION,
        self::ATTR_PERSISTENT => true,
    ];

    /**
     * Create a new PDO object from a Connection object.
     */
    public static function fromConnection(Connection $connection, array $options = []): self
    {
        return new self($connection->getDsn(), $connection->getUser(), $connection->getPassword(), array_merge(self::DEFAULT_OPTIONS, $options));
    }
}
