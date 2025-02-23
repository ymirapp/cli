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

use Ymir\Cli\Project\Configuration\WordPress\OxygenConfigurationChange;
use Ymir\Cli\Tests\Mock\AbstractWordPressProjectMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\WordPress\OxygenConfigurationChange
 */
class OxygenConfigurationChangeTest extends TestCase
{
    use AbstractWordPressProjectMockTrait;

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
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

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
                'excluded_paths' => ['/uploads/oxygen/*'],
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
                'wp-content/plugins/oxygen',
            ]],
            'cdn' => [
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
        ], $this->configurationChange->apply([], $projectType));
    }

    public function testGetName()
    {
        $this->assertSame('oxygen', $this->configurationChange->getName());
    }
}
