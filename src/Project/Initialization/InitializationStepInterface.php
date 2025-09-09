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

namespace Ymir\Cli\Project\Initialization;

use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;

interface InitializationStepInterface
{
    /**
     * Perform the initialization step.
     *
     * This method orchestrates the interaction with the user and the execution of any necessary actions. It should
     * return a ConfigurationChangeInterface if the ymir.yml file needs to be updated with the results of this step.
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface;
}
