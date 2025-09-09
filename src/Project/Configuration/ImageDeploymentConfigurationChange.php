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

class ImageDeploymentConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        return $configuration->without('php')->with(['deployment' => 'image']);
    }
}
