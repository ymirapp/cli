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

use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;

abstract class AbstractWordPressConfigurationChange implements WordPressConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        if (!$projectType instanceof AbstractWordPressProjectType) {
            throw new LogicException('Can only apply these configuration changes to WordPress projects');
        }

        $buildIncludePaths = $this->getBuildIncludePaths($projectType);
        $configurationChange = $this->getConfiguration();

        if (!$configuration->isImageDeploymentType() && !empty($buildIncludePaths)) {
            Arr::set($configurationChange, 'build.include', $buildIncludePaths);
        }

        return $configuration->merge($configurationChange);
    }

    /**
     * Get the base path to use with build include option based on the project type.
     */
    protected function getBaseIncludePath(AbstractWordPressProjectType $projectType): string
    {
        return $projectType->getPluginsDirectoryPath().'/'.$this->getName();
    }

    /**
     * Get the build include paths to merge into the options when using zip archive deployment.
     */
    protected function getBuildIncludePaths(AbstractWordPressProjectType $projectType): array
    {
        return [];
    }

    /**
     * Get the configuration to merge into the environment.
     */
    protected function getConfiguration(): array
    {
        return [];
    }
}
