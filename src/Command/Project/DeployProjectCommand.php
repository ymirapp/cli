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

namespace Placeholder\Cli\Command\Project;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\Deployment\DeploymentStepInterface;
use Placeholder\Cli\ProjectConfiguration;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class DeployProjectCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:deploy';

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
            ->setAliases(['deploy'])
            ->setDescription('Deploy project to an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = $input->getArgument('environment');

        if (!is_string($environment)) {
            throw new RuntimeException('Invalid "environment" argument given');
        }

        $this->invoke($output, ValidateProjectCommand::NAME, ['environments' => $environment]);
        $this->invoke($output, BuildProjectCommand::NAME);

        $deploymentId = (int) $this->apiClient->createDeployment($this->projectConfiguration->getProjectId(), $environment, $this->projectConfiguration, Uuid::uuid4()->toString())->get('id');

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
