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

namespace Ymir\Cli\Tests\Unit\Database;

use Ymir\Cli\Database\Connection;
use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\TestCase;

class ConnectionTest extends TestCase
{
    public function testGetDsnReturnsMysqlDsn(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createMysql(), 'user', 'password');

        $this->assertSame('mysql:host=db.example.com;port=3306;dbname=database;charset=utf8mb4', $connection->getDsn());
    }

    public function testGetDsnReturnsPostgresqlDsn(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createPostgresql(), 'user', 'password');

        $this->assertSame('pgsql:host=db.example.com;port=5432;dbname=database', $connection->getDsn());
    }

    public function testGetDsnThrowsExceptionIfEngineUnsupported(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createUnsupportedEngine(), 'user', 'password');

        $this->expectException(UnsupportedDatabaseServerEngineException::class);
        $this->expectExceptionMessage('Unsupported database server engine "unsupported".');

        $connection->getDsn();
    }

    public function testGetPortReturnsLocalMysqlPortForPrivateDatabaseServer(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createMysql(['publicly_accessible' => false]), 'user', 'password');

        $this->assertSame('3305', $connection->getPort());
    }

    public function testGetPortReturnsLocalPostgresqlPortForPrivateDatabaseServer(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createPostgresql(['publicly_accessible' => false]), 'user', 'password');

        $this->assertSame('5433', $connection->getPort());
    }

    public function testGetPortReturnsMysqlPortForPublicDatabaseServer(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createMysql(), 'user', 'password');

        $this->assertSame('3306', $connection->getPort());
    }

    public function testGetPortReturnsPostgresqlPortForPublicDatabaseServer(): void
    {
        $connection = new Connection('database', DatabaseServerFactory::createPostgresql(), 'user', 'password');

        $this->assertSame('5432', $connection->getPort());
    }
}
