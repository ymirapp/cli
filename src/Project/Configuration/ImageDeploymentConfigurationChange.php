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

namespace Ymir\Cli\Project\Configuration;

use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;

class ImageDeploymentConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        $deployment = Arr::get($configuration->toArray(), 'deployment');

        return $configuration->without('php')->with(['deployment' => is_array($deployment) ? array_merge($deployment, ['type' => 'image']) : 'image']);
    }
}
