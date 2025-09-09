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
use Ymir\Cli\Project\Type\ProjectTypeInterface;

class IntegrationInitializationStep implements InitializationStepInterface
{
    /**
     * {@inheritDoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        if (empty($projectRequirements['type']) || !$projectRequirements['type'] instanceof ProjectTypeInterface) {
            return null;
        }

        $output = $context->getOutput();
        $projectDirectory = $context->getProjectDirectory();
        $projectType = $projectRequirements['type'];

        if (!$projectType->isIntegrationInstalled($projectDirectory) && $output->confirm(sprintf('Would you like to install the Ymir integration for <comment>%s</comment>?', $projectType->getName()))) {
            $projectType->installIntegration($projectDirectory);

            $output->info(sprintf('Ymir <comment>%s</comment> integration installed', $projectType->getName()));
        }

        return null;
    }
}
