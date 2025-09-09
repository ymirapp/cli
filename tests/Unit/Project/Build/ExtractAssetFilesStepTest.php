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
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Project\Build\ExtractAssetFilesStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class ExtractAssetFilesStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new ExtractAssetFilesStep('assets', 'build', \Mockery::mock(Filesystem::class));

        $this->assertSame('Extracting asset files', $step->getDescription());
    }

    public function testPerformExtractsAssetFiles(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $projectType = \Mockery::mock(ProjectTypeInterface::class);

        $projectConfiguration->shouldReceive('getProjectType')->andReturn($projectType);
        $projectType->shouldReceive('getAssetFiles')->andReturn(Finder::create()->append([]));

        $filesystem->shouldReceive('exists')->once()
                   ->with('assets')
                   ->andReturn(true);
        $filesystem->shouldReceive('remove')->once()
                   ->with('assets');
        $filesystem->shouldReceive('mkdir')->once()
                   ->with('assets', 0755);

        $step = new ExtractAssetFilesStep('assets', 'build', $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
