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

use Ymir\Cli\Support\Arr;

abstract class AbstractWordPressConfigurationChange implements WordPressConfigurationChangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(array $options, string $projectType): array
    {
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
    protected function getBaseIncludePath(string $projectType): string
    {
        $basePath = 'bedrock' === $projectType ? 'web/app' : 'wp-content';

        return $basePath.'/plugins/'.$this->getName();
    }

    /**
     * Get the build include paths to merge into the options when using zip archive deployment.
     */
    protected function getBuildIncludePaths(string $projectType): array
    {
        return [];
    }

    /**
     * Get the options to merge into the project configuration.
     */
    abstract protected function getOptionsToMerge(): array;
}
