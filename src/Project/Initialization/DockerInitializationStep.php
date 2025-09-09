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

use Ymir\Cli\Dockerfile;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;

class DockerInitializationStep implements InitializationStepInterface
{
    /**
     * Docker executable.
     *
     * @var DockerExecutable
     */
    private $dockerExecutable;

    /**
     * The project Dockerfile.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * Constructor.
     */
    public function __construct(DockerExecutable $dockerExecutable, Dockerfile $dockerfile)
    {
        $this->dockerExecutable = $dockerExecutable;
        $this->dockerfile = $dockerfile;
    }

    /**
     * {@inheritDoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        $output = $context->getOutput();

        if (!$output->confirm('Do you want to deploy this project using a container image?')) {
            return null;
        }

        if (!$this->dockerfile->exists() || $output->confirm('A <comment>Dockerfile</comment> already exists in the project directory. Do you want to overwrite it?', false)) {
            $this->dockerfile->create();
        }

        if (!$this->dockerExecutable->isInstalled()) {
            $output->warning('<comment>Docker</comment> wasn\'t detected and is required to deploy the project locally');
        }

        return new ImageDeploymentConfigurationChange();
    }
}
