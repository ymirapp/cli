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

namespace Placeholder\Cli\Command;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\Deployment\DeploymentStepInterface;
use Placeholder\Cli\ProjectConfiguration;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class DeployCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'deploy';

    /**
     * The deployment steps to perform.
     *
     * @var DeploymentStepInterface[]
     */
    private $deploymentSteps;

    /**
     * The placeholder project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->deploymentSteps = [];
        $this->projectConfiguration = $projectConfiguration;

        foreach ($deploymentSteps as $deploymentStep) {
            $this->addDeploymentStep($deploymentStep);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deploy project to an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        if (!$this->projectConfiguration->exists()) {
            throw new RuntimeException('No project configuration file found');
        }

        $output->info('Validating project configuration');

        $this->projectConfiguration->validate();

        $environment = $input->getArgument('environment');

        if (!is_string($environment)) {
            throw new RuntimeException('Invalid "environment" argument given');
        } elseif (!$this->projectConfiguration->hasEnvironment($environment)) {
            throw new RuntimeException(sprintf('The "%s" environment isn\'t configured in your configuration', $environment));
        }

        // TODO: Also validate configuration remotely

        $projectId = $this->projectConfiguration->getProjectId();
        $uuid = Uuid::uuid4()->toString();

        $this->invoke($output, BuildCommand::NAME);

        $deployment = $this->apiClient->createDeployment($projectId, $environment, $uuid);
        $deploymentId = (int) $deployment->get('id');

        if (empty($deploymentId)) {
            throw new RuntimeException('There was an error creating the deployment');
        }

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($deploymentId, $output);
        }

        $output->info('Project deployed successfully');
    }

    /**
     * Add a deployment step to the command.
     */
    private function addDeploymentStep(DeploymentStepInterface $deploymentStep)
    {
        $this->deploymentSteps[] = $deploymentStep;
    }
}
