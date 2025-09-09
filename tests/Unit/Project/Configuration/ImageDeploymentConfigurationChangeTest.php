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

namespace Ymir\Cli\Tests\Unit\Project\Configuration;

use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class ImageDeploymentConfigurationChangeTest extends TestCase
{
    public function testApplySetsDeploymentToImageAndRemovesPhpNode(): void
    {
        $change = new ImageDeploymentConfigurationChange();
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['php' => '7.4', 'foo' => 'bar']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['foo' => 'bar', 'deployment' => 'image'], $environmentConfiguration->toArray());
    }
}
