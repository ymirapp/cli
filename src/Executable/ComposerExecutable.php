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

namespace Ymir\Cli\Executable;

class ComposerExecutable extends AbstractExecutable
{
    /**
     * Create a new project from the given package into the given directory.
     */
    public function createProject(string $package, string $directory = '.'): void
    {
        $this->run(sprintf('create-project %s %s', $package, $directory));
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'Composer';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        return 'composer';
    }

    /**
     * Check if the given package is installed.
     */
    public function isPackageInstalled(string $package, ?string $cwd = null): bool
    {
        try {
            $this->run(sprintf('show %s', $package), $cwd);

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Add the given package to the project's "composer.json" file and install it.
     */
    public function require(string $package, ?string $cwd = null): void
    {
        $this->run(sprintf('require %s', $package), $cwd);
    }
}
