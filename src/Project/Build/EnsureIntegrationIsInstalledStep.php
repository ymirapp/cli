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

namespace Ymir\Cli\Project\Build;

use Ymir\Cli\Command\InstallIntegrationCommand;
use Ymir\Cli\Exception\Project\BuildFailedException;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;

class EnsureIntegrationIsInstalledStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Ensuring Ymir integration is installed';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(EnvironmentConfiguration $environmentConfiguration, ProjectConfiguration $projectConfiguration): void
    {
        if (!$projectConfiguration->getProjectType()->isIntegrationInstalled($this->buildDirectory)) {
            throw new BuildFailedException(sprintf('Ymir integration is not installed in the build directory, but you can install it with the "%s" command', InstallIntegrationCommand::NAME));
        }
    }
}
