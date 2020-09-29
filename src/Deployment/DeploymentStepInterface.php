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

namespace Ymir\Cli\Deployment;

use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Console\OutputStyle;

interface DeploymentStepInterface
{
    /**
     * Perform the deployment step and generate the console output.
     */
    public function perform(Collection $deployment, OutputStyle $output);
}
