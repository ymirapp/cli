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

namespace Ymir\Cli\Project\Deployment;

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;

interface DeploymentStepInterface
{
    /**
     * Perform the deployment step and generate the console output.
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment);
}
