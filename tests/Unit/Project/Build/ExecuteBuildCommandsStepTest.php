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

namespace Ymir\Cli\Tests\Unit\Project\Build;

use Ymir\Cli\Project\Build\ExecuteBuildCommandsStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Tests\TestCase;

class ExecuteBuildCommandsStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new ExecuteBuildCommandsStep('build');

        $this->assertSame('Executing build commands', $step->getDescription());
    }

    public function testPerformDoesNothingIfNoBuildConfiguration(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $environmentConfiguration->shouldReceive('hasBuildConfiguration')->once()
                                 ->andReturn(false);

        $environmentConfiguration->shouldReceive('getBuildCommands')->never();

        $step = new ExecuteBuildCommandsStep('build');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
