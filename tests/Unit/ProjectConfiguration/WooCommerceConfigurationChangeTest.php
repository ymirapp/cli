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

namespace Ymir\Cli\Tests\Unit\ProjectConfiguration;

use PHPUnit\Framework\TestCase;
use Ymir\Cli\ProjectConfiguration\WooCommerceConfigurationChange;

/**
 * @covers \Ymir\Cli\ProjectConfiguration\WooCommerceConfigurationChange
 */
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

    public function testApplyDoesntDuplicateExistingOptions()
    {
        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
            ],
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
            ],
        ], 'wordpress'));
    }

    public function testApplyDoesntEraseExistingOptions()
    {
        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/foo',
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
            ],
        ], $this->configurationChange->apply([
            'build' => ['include' => ['wp-content/plugins/foo']],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie'],
                'excluded_paths' => ['/foo'],
            ],
        ], 'wordpress'));
    }

    public function testApplyWithBedrockProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'bedrock'));
    }

    public function testApplyWithBedrockProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'build' => ['include' => ['web/app/plugins/woocommerce']],
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
            ],
        ], $this->configurationChange->apply([], 'bedrock'));
    }

    public function testApplyWithWordPressProjectAndImageDeployment()
    {
        $this->assertSame([
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
            ],
            'deployment' => 'image',
        ], $this->configurationChange->apply(['deployment' => 'image'], 'wordpress'));
    }

    public function testApplyWithWordPressProjectAndNoImageDeployment()
    {
        $this->assertSame([
            'build' => ['include' => ['wp-content/plugins/woocommerce']],
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
            ],
        ], $this->configurationChange->apply([], 'wordpress'));
    }

    public function testGetName()
    {
        $this->assertSame('woocommerce', $this->configurationChange->getName());
    }
}
