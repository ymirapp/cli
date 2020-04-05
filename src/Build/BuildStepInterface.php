<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Build;

interface BuildStepInterface
{
    /**
     * Get the description of the build step.
     */
    public function getDescription(): string;

    /**
     * Perform the build step.
     */
    public function perform(string $environment);
}
