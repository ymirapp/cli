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

namespace Placeholder\Cli\Deployment;

use Placeholder\Cli\Console\OutputStyle;

interface DeploymentStepInterface
{
    /**
     * Perform the deployment step and generate the console output.
     */
    public function perform(int $deploymentId, OutputStyle $output);
}
