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

namespace Ymir\Cli\Tests\Unit\ProjectConfiguration\WordPress;

use Ymir\Cli\ProjectConfiguration\WordPress\BeaverBuilderConfigurationChange;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\ProjectConfiguration\WordPress\BeaverBuilderConfigurationChange
 */
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

    public function testApplyDoesntDuplicateExistingOptions()
    {
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
        ], 'wordpress'));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
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
        ], 'wordpress'));
    }

    public function testApplyWithBedrockProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'bedrock'));
    }

    public function testApplyWithBedrockProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'build' => ['include' => [
                'web/app/plugins/bb-plugin/fonts',
                'web/app/plugins/bb-plugin/img',
                'web/app/plugins/bb-plugin/js',
                'web/app/plugins/bb-plugin/json',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ], $this->configurationChange->apply([], 'bedrock'));
    }

    public function testApplyWithWordPressProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'wordpress'));
    }

    public function testApplyWithWordPressProjectAndNoImageDeployment()
    {
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
        ], $this->configurationChange->apply([], 'wordpress'));
    }

    public function testGetName()
    {
        $this->assertSame('bb-plugin', $this->configurationChange->getName());
    }
}
