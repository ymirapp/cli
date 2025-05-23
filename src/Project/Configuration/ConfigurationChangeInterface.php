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

namespace Ymir\Cli\Project\Configuration;

use Ymir\Cli\Project\Type\ProjectTypeInterface;

interface ConfigurationChangeInterface
{
    /**
     * Apply the configuration changes.
     */
    public function apply(array $options, ProjectTypeInterface $projectType): array;
}
