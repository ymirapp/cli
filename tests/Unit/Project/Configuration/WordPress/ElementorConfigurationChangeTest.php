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

use Ymir\Cli\Project\Configuration\WordPress\ElementorConfigurationChange;
use Ymir\Cli\Tests\Mock\AbstractWordPressProjectMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\WordPress\ElementorConfigurationChange
 */
class ElementorConfigurationChangeTest extends TestCase
{
    use AbstractWordPressProjectMockTrait;

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
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $projectType));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([
            'cdn' => [
                'excluded_paths' => ['/foo'],
            ],
        ], $projectType));
    }

    public function testApplyWithImageDeployment()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], $projectType));
    }

    public function testApplyWithNoImageDeployment()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply([], $projectType));
    }

    public function testGetName()
    {
        $this->assertSame('elementor', $this->configurationChange->getName());
    }
}
