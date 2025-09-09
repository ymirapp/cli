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

use Ymir\Cli\Project\Configuration\WordPress\WooCommerceConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\TestCase;

class WooCommerceConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new WooCommerceConfigurationChange();
    }

    public function testApplyDoesntDuplicateExistingOptions(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $projectType->shouldReceive('getPluginsDirectoryPath')->once()
                    ->andReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
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
                'wp-content/plugins/foo',
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', [
            'build' => ['include' => ['wp-content/plugins/foo']],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie'],
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
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
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
            'build' => ['include' => ['wp-content/plugins/woocommerce']],
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', []), $projectType)->toArray());
    }

    public function testGetName(): void
    {
        $this->assertSame('woocommerce', $this->configurationChange->getName());
    }
}
