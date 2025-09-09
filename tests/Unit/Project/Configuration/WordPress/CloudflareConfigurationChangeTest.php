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
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\TestCase;

class CloudflareConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new CloudflareConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => [
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ]), $projectType)->toArray());
    }

    public function testApplyDoesntEraseExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/cloudflare/config.json',
                'wp-content/plugins/foo',
            ]],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => [
                'wp-content/plugins/foo',
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ]), $projectType)->toArray());
    }

    public function testApplyWithImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
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
                'wp-content/plugins/cloudflare/config.json',
            ]],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', []), $projectType)->toArray());
    }

    public function testGetName(): void
    {
        $this->assertSame('cloudflare', $this->configurationChange->getName());
    }
}
