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

namespace Ymir\Cli\Tests\Unit\Resource\Requirement;

use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\ParentDatabaseServerRequirement;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\TestCase;

class ParentDatabaseServerRequirementTest extends TestCase
{
    public function testFulfillReturnsDatabaseServerFromContext(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $databaseServer = DatabaseServerFactory::create();

        $context->shouldReceive('getParentResource')->andReturn($databaseServer);

        $requirement = new ParentDatabaseServerRequirement();

        $this->assertSame($databaseServer, $requirement->fulfill($context));
    }

    public function testFulfillThrowsExceptionIfParentResourceIsNotDatabaseServer(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A DatabaseServer must be resolved and passed into the context before fulfilling its dependencies');

        $context->shouldReceive('getParentResource')->andReturn(null);

        $requirement = new ParentDatabaseServerRequirement();

        $requirement->fulfill($context);
    }
}
