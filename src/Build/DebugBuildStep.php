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

namespace Ymir\Cli\Build;

use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class DebugBuildStep implements BuildStepInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '<info>Debug mode:</info> Press <comment>Enter</comment> to continue.';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        fgets(STDIN);
    }
}
