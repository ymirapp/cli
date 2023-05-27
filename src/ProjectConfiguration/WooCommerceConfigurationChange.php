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

namespace Ymir\Cli\ProjectConfiguration;

class WooCommerceConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'woocommerce';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBuildIncludePaths(string $projectType): array
    {
        return [
            $this->getBaseIncludePath($projectType),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsToMerge(): array
    {
        return [
            'cdn' => [
                'cookies_whitelist' => ['woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed', 'wp_woocommerce_session_*'],
                'excluded_paths' => ['/addons', '/cart', '/checkout', '/my-account'],
                'forwarded_headers' => ['authorization', 'origin', 'x-http-method-override', 'x-wp-nonce'],
            ],
        ];
    }
}
