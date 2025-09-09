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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Build\CopyMustUsePluginStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class CopyMustUsePluginStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new CopyMustUsePluginStep('build', \Mockery::mock(Filesystem::class), 'stub');

        $this->assertSame('Copying Ymir must-use plugin', $step->getDescription());
    }

    public function testPerformCopiesMustUsePlugin(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getMustUsePluginsDirectoryPath')->andReturn('build/wp-content/mu-plugins');

        $filesystem->shouldReceive('exists')->andReturn(true);
        $filesystem->shouldReceive('copy')->once()
                   ->with('stub/activate-ymir-plugin.php', 'build/wp-content/mu-plugins/activate-ymir-plugin.php');

        $step = new CopyMustUsePluginStep('build', $filesystem, 'stub');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }

    public function testPerformThrowsExceptionWithUnsupportedProjectType(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this build step with WordPress projects');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $step = new CopyMustUsePluginStep('build', \Mockery::mock(Filesystem::class), 'stub');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
