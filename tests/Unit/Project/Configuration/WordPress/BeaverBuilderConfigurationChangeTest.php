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

namespace Ymir\Cli\Tests\Unit\Project\Configuration\WordPress;

use Ymir\Cli\Project\Configuration\WordPress\BeaverBuilderConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\TestCase;

class BeaverBuilderConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new BeaverBuilderConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/bb-plugin/fonts',
                'wp-content/plugins/bb-plugin/img',
                'wp-content/plugins/bb-plugin/js',
                'wp-content/plugins/bb-plugin/json',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => [
                'wp-content/plugins/bb-plugin/fonts',
                'wp-content/plugins/bb-plugin/img',
                'wp-content/plugins/bb-plugin/js',
                'wp-content/plugins/bb-plugin/json',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ]), $projectType)->toArray());
    }

    public function testApplyDoesntEraseExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/bb-plugin/fonts',
                'wp-content/plugins/bb-plugin/img',
                'wp-content/plugins/bb-plugin/js',
                'wp-content/plugins/bb-plugin/json',
                'wp-content/plugins/foo',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/bb-plugin/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => [
                'wp-content/plugins/foo',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo'],
            ],
        ]), $projectType)->toArray());
    }

    public function testApplyWithImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', ['deployment' => 'image']), $projectType)->toArray());
    }

    public function testApplyWithNoImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/bb-plugin/fonts',
                'wp-content/plugins/bb-plugin/img',
                'wp-content/plugins/bb-plugin/js',
                'wp-content/plugins/bb-plugin/json',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', []), $projectType)->toArray());
    }

    public function testGetName(): void
    {
        $this->assertSame('bb-plugin', $this->configurationChange->getName());
    }
}
