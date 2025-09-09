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
use Ymir\Cli\Project\Build\DownloadWpCliStep;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Tests\TestCase;

class DownloadWpCliStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new DownloadWpCliStep('build', \Mockery::mock(Filesystem::class));

        $this->assertSame('Downloading WP-CLI', $step->getDescription());
    }

    public function testPerformDownloadsWpCli(): void
    {
        $environmentConfiguration = \Mockery::mock(EnvironmentConfiguration::class);
        $filesystem = \Mockery::mock(Filesystem::class);
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $filesystem->shouldReceive('exists')->with('build/bin')->andReturn(false);
        $filesystem->shouldReceive('mkdir')->once()
                   ->with('build/bin', 0755);
        $filesystem->shouldReceive('copy')->once()
                   ->with($this->stringContains('https://github.com/wp-cli/wp-cli/releases/download'), 'build/bin/wp', true);
        $filesystem->shouldReceive('chmod')->once()
                   ->with('build/bin/wp', 0755);

        $step = new DownloadWpCliStep('build', $filesystem);

        $step->perform($environmentConfiguration, $projectConfiguration);
    }
}
