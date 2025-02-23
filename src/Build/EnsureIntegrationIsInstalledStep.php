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

use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

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
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        if (!$projectConfiguration->getProjectType()->isIntegrationInstalled($this->buildDirectory)) {
            throw new RuntimeException('Ymir integration is not installed in the build directory');
        }
    }
}
