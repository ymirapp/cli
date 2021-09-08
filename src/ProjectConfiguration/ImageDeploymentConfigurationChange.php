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

class ImageDeploymentConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(array $options, string $projectType): array
    {
        return array_merge($options, ['deployment' => 'image']);
    }
}
