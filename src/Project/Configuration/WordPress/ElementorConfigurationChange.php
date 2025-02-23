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

class ElementorConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'elementor';
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsToMerge(): array
    {
        return [
            'cdn' => [
                'excluded_paths' => ['/uploads/elementor/*'],
            ],
        ];
    }
}
