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
use Ymir\Cli\Tests\Mock\AbstractWordPressProjectMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\WordPress\BeaverBuilderConfigurationChange
 */
class BeaverBuilderConfigurationChangeTest extends TestCase
{
    use AbstractWordPressProjectMockTrait;

    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new BeaverBuilderConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

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
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/bb-plugin/fonts',
                'wp-content/plugins/bb-plugin/img',
                'wp-content/plugins/bb-plugin/js',
                'wp-content/plugins/bb-plugin/json',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ], $projectType));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

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
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/foo',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo'],
            ],
        ], $projectType));
    }

    public function testApplyWithImageDeployment()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], $projectType));
    }

    public function testApplyWithNoImageDeployment()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

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
        ], $this->configurationChange->apply([], $projectType));
    }

    public function testGetName()
    {
        $this->assertSame('bb-plugin', $this->configurationChange->getName());
    }
}
