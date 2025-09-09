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

use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Project\Build\EnsureIntegrationIsInstalledStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class EnsureIntegrationIsInstalledStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new EnsureIntegrationIsInstalledStep('build');

        $this->assertSame('Ensuring Ymir integration is installed', $step->getDescription());
    }

    public function testPerformDoesNothingIfIntegrationInstalled(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('isIntegrationInstalled')->once()
                    ->with('build')
                    ->andReturn(true);

        $step = new EnsureIntegrationIsInstalledStep('build');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformThrowsExceptionIfIntegrationNotInstalled(): void
    {
        $this->expectException(BuildFailedException::class);
        $this->expectExceptionMessage('Ymir integration is not installed in the build directory');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('isIntegrationInstalled')->once()
                    ->with('build')
                    ->andReturn(false);

        $step = new EnsureIntegrationIsInstalledStep('build');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
