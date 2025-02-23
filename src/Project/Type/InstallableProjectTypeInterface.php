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

interface InstallableProjectTypeInterface extends ProjectTypeInterface
{
    /**
     * Get the message to display to the user when installing the project.
     */
    public function getInstallationMessage(): string;

    /**
     * Install the project in the given directory.
     */
    public function installProject(string $directory);

    /**
     * Determines if the project is eligible for installation in the given directory.
     *
     * Returns true when the project isn't already installed in the given directory and all prerequisites for
     * installation are met.
     */
    public function isEligibleForInstallation(string $directory): bool;
}
