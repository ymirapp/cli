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

namespace Ymir\Cli\Project\Type;

use Symfony\Component\Finder\Finder;

interface SupportsMediaInterface
{
    /**
     * Get the user-friendly name of the media directory.
     */
    public function getMediaDirectoryName(): string;

    /**
     * Get the relative or absolute path to the media directory.
     *
     * If $projectDirectory is provided, an absolute path should be returned. Otherwise, the path should
     * be relative to the project root.
     */
    public function getMediaDirectoryPath(string $projectDirectory = ''): string;

    /**
     * Get the Finder object for finding all the media files in the given project directory.
     */
    public function getMediaFiles(string $projectDirectory): Finder;
}
