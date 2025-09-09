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
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Requirement\EnvironmentsRequirement;
use Ymir\Cli\Tests\TestCase;

class EnvironmentsRequirementTest extends TestCase
{
    public function testFulfillReturnsDefaultEnvironments(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $requirement = new EnvironmentsRequirement();

        $this->assertSame(Project::DEFAULT_ENVIRONMENTS, $requirement->fulfill($context));
    }
}
