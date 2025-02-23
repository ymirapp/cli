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

class CloudflareConfigurationChange extends AbstractWordPressConfigurationChange
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'cloudflare';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBuildIncludePaths(AbstractWordPressProjectType $projectType): array
    {
        return [
            $this->getBaseIncludePath($projectType).'/config.json',
        ];
    }
}
