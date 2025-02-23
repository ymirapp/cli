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
use Ymir\Cli\Tests\Mock\AbstractWordPressProjectMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\WordPress\WooCommerceConfigurationChange
 */
class WooCommerceConfigurationChangeTest extends TestCase
{
    use AbstractWordPressProjectMockTrait;

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
        $projectType = $this->getAbstractWordPressProjectTypeMock();

        $projectType->expects($this->once())
                    ->method('getPluginsDirectoryPath')
                    ->willReturn('wp-content/plugins');

        $this->assertSame([
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply([
            'build' => ['include' => [
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
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
                'wp-content/plugins/woocommerce',
            ]],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie', 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/foo', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply([
            'build' => ['include' => ['wp-content/plugins/foo']],
            'cdn' => [
                'cookies_whitelist' => ['foo_cookie'],
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
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
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
            'build' => ['include' => ['wp-content/plugins/woocommerce']],
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ], $this->configurationChange->apply([], $projectType));
    }

    public function testGetName()
    {
        $this->assertSame('woocommerce', $this->configurationChange->getName());
    }
}
