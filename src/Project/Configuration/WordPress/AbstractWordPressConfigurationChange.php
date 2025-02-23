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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;

abstract class AbstractWordPressConfigurationChange implements WordPressConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(array $options, ProjectTypeInterface $projectType): array
    {
        if (!$projectType instanceof AbstractWordPressProjectType) {
            throw new InvalidArgumentException('Can only apply these configuration changes to WordPress projects');
        }

        $buildIncludePaths = $this->getBuildIncludePaths($projectType);
        $optionsToMerge = $this->getOptionsToMerge();

        if ('image' !== Arr::get($options, 'deployment') && !empty($buildIncludePaths)) {
            Arr::set($optionsToMerge, 'build.include', $buildIncludePaths);
        }

        return Arr::sortRecursive(Arr::uniqueRecursive(array_merge_recursive($options, $optionsToMerge)));
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
     * Get the options to merge into the project configuration.
     */
    protected function getOptionsToMerge(): array
    {
        return [];
    }
}
