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

use Ymir\Cli\Project\Configuration\WordPress\CloudflareConfigurationChange;
use Ymir\Cli\Tests\Mock\AbstractWordPressProjectMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\WordPress\CloudflareConfigurationChange
 */
class CloudflareConfigurationChangeTest extends TestCase
{
    use AbstractWordPressProjectMockTrait;

    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new CloudflareConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/cloudflare/config.json',
            ]],
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
                'wp-content/plugins/cloudflare/config.json',
                'wp-content/plugins/foo',
            ]],
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/foo',
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ], $projectType));
    }

    public function testApplyWithImageDeployment()
    {
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

        $this->assertSame([
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
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ], $this->configurationChange->apply([], $projectType));
    }

    public function testGetName()
    {
        $this->assertSame('cloudflare', $this->configurationChange->getName());
    }
}
