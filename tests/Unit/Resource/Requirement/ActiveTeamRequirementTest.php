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

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\ActiveTeamRequirement;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class ActiveTeamRequirementTest extends TestCase
{
    public function testFulfillReturnsTeamFromContext(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $team = TeamFactory::create();

        $context->shouldReceive('getTeam')->andReturn($team);

        $requirement = new ActiveTeamRequirement();

        $this->assertSame($team, $requirement->fulfill($context));
    }
}
