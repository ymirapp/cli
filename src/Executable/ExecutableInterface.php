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

interface ExecutableInterface
{
    /**
     * Get the human-readable name for this executable.
     */
    public function getDisplayName(): string;

    /**
     * Get the actual binary name or command string used to invoke this executable.
     */
    public function getExecutable(): string;

    /**
     * Determines if this command-line executable is installed and accessible globally.
     */
    public function isInstalled(): bool;
}
