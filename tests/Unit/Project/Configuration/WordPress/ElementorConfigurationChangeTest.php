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
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\TestCase;

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

    public function testApplyDoesntDuplicateExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ]), $projectType)->toArray());
    }

    public function testApplyDoesntEraseExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/foo', '/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'cdn' => [
                'excluded_paths' => ['/foo'],
            ],
        ]), $projectType)->toArray());
    }

    public function testApplyWithImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', ['deployment' => 'image']), $projectType)->toArray());
    }

    public function testApplyWithNoImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $this->assertSame([
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', []), $projectType)->toArray());
    }

    public function testGetName(): void
    {
        $this->assertSame('elementor', $this->configurationChange->getName());
    }
}
