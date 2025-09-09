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
use Ymir\Cli\Project\Build\CopyMediaDirectoryStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class CopyMediaDirectoryStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new CopyMediaDirectoryStep(\Mockery::mock(Filesystem::class), 'project', 'uploads');

        $this->assertSame('Copying media directory', $step->getDescription());
    }

    public function testPerformThrowsExceptionWithUnsupportedProjectType(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this build step with projects that support media operations');

        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);

        $step = new CopyMediaDirectoryStep(\Mockery::mock(Filesystem::class), 'project', 'uploads');

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
