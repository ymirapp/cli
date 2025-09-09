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

namespace Ymir\Cli\Project\Configuration\WordPress;

class NorthCommerceConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'north-commerce';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(): array
    {
        return [
            'cdn' => [
                'cookies_whitelist' => ['nc-cart-order-id'],
                'excluded_paths' => ['/cart', '/checkout'],
            ],
        ];
    }
}
