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

use Ymir\Cli\ProjectConfiguration\WordPress\ElementorConfigurationChange;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\ProjectConfiguration\WordPress\ElementorConfigurationChange
 */
class ElementorConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new ElementorConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], 'wordpress'));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([
            'cdn' => [
                'excluded_paths' => ['/foo'],
            ],
        ], 'wordpress'));
    }

    public function testApplyWithBedrockProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'bedrock'));
    }

    public function testApplyWithBedrockProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([], 'bedrock'));
    }

    public function testApplyWithWordPressProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'wordpress'));
    }

    public function testApplyWithWordPressProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([], 'wordpress'));
    }

    public function testGetName()
    {
        $this->assertSame('elementor', $this->configurationChange->getName());
    }
}
