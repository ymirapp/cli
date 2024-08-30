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

namespace Ymir\Cli\ProjectConfiguration\WordPress;

class OxygenConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'oxygen';
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
                'excluded_paths' => ['/uploads/oxygen/*'],
            ],
        ];
    }
}
