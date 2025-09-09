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
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Build\ModifyWordPressConfigurationStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class ModifyWordPressConfigurationStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new ModifyWordPressConfigurationStep('build', \Mockery::mock(Filesystem::class), 'stub');

        $this->assertSame('Modifying WordPress configuration', $step->getDescription());
    }

    public function testPerformThrowsExceptionIfNoWpConfigFileFound(): void
    {
        $this->expectException(BuildFailedException::class);
        $this->expectExceptionMessage('No wp-config.php or wp-config-sample.php found in the build directory');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $filesystem->shouldReceive('exists')->andReturn(false);

        $step = new ModifyWordPressConfigurationStep('build', $filesystem, 'stub');

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

        $step = new ModifyWordPressConfigurationStep('build', \Mockery::mock(Filesystem::class), 'stub');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
