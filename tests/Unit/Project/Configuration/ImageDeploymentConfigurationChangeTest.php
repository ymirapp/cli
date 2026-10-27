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
    public static function provideDeploymentHealthchecks(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
            'custom path' => ['/up'],
        ];
    }

    /**
     * @dataProvider provideDeploymentHealthchecks
     */
    public function testApplyPreservesDeploymentOptions($healthcheck): void
    {
        $change = new ImageDeploymentConfigurationChange();
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['php' => '7.4', 'deployment' => ['type' => 'zip', 'commands' => ['php artisan migrate'], 'healthcheck' => $healthcheck], 'foo' => 'bar']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['deployment' => ['type' => 'image', 'commands' => ['php artisan migrate'], 'healthcheck' => $healthcheck], 'foo' => 'bar'], $environmentConfiguration->toArray());
    }

    public function testApplyReplacesDeploymentTypeString(): void
    {
        $change = new ImageDeploymentConfigurationChange();
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['php' => '7.4', 'deployment' => 'zip']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['deployment' => 'image'], $environmentConfiguration->toArray());
    }

    public function testApplySetsDeploymentToImageAndRemovesPhpNode(): void
    {
        $change = new ImageDeploymentConfigurationChange();
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['php' => '7.4', 'foo' => 'bar']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['foo' => 'bar', 'deployment' => 'image'], $environmentConfiguration->toArray());
    }

    public function testApplySetsDeploymentTypeWithoutType(): void
    {
        $change = new ImageDeploymentConfigurationChange();
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['deployment' => ['commands' => ['php artisan migrate']]]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['deployment' => ['commands' => ['php artisan migrate'], 'type' => 'image']], $environmentConfiguration->toArray());
    }
}
