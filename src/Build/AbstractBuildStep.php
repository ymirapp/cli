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

abstract class AbstractBuildStep implements BuildStepInterface
{
    /**
     * {@inheritdoc}
     */
    public function isNeeded(array $buildOptions, ProjectConfiguration $projectConfiguration): bool
    {
        return true;
    }
}
