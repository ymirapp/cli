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

use Ymir\Cli\ProjectConfiguration\WordPress\OxygenConfigurationChange;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\ProjectConfiguration\WordPress\OxygenConfigurationChange
 */
class OxygenConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new OxygenConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions()
    {
        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/oxygen/*'],
            ],
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/oxygen/*'],
            ],
        ], 'wordpress'));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/foo',
                'wp-content/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/oxygen/*'],
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
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'bedrock'));
    }

    public function testApplyWithBedrockProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'build' => ['include' => [
                'web/app/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
        ], $this->configurationChange->apply([], 'bedrock'));
    }

    public function testApplyWithWordPressProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'wordpress'));
    }

    public function testApplyWithWordPressProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
        ], $this->configurationChange->apply([], 'wordpress'));
    }

    public function testGetName()
    {
        $this->assertSame('oxygen', $this->configurationChange->getName());
    }
}
