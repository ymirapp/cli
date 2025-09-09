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

use Ymir\Cli\Project\Type\AbstractWordPressProjectType;

class BeaverBuilderConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'bb-plugin';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBuildIncludePaths(AbstractWordPressProjectType $projectType): array
    {
        $basePath = $this->getBaseIncludePath($projectType);

        return [
            $basePath.'/fonts',
            $basePath.'/img',
            $basePath.'/js',
            $basePath.'/json',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(): array
    {
        return [
            'cdn' => [
                'excluded_paths' => ['/uploads/bb-plugin/*'],
            ],
        ];
    }
}
