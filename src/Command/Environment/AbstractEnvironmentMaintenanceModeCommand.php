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

namespace Ymir\Cli\Command\Environment;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Deployment\DeploymentStepInterface;
use Ymir\Cli\Resource\Model\Environment;

abstract class AbstractEnvironmentMaintenanceModeCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The deployment steps to perform.
     *
     * @var DeploymentStepInterface[]
     */
    private $deploymentSteps;

    /**
     * Create the command with its deployment steps.
     *
     * @param DeploymentStepInterface[] $deploymentSteps
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, array $deploymentSteps)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->deploymentSteps = [];

        foreach ($deploymentSteps as $deploymentStep) {
            $this->addDeploymentStep($deploymentStep);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(): void
    {
        $environment = $this->resolve(Environment::class, $this->getEnvironmentQuestion());

        if (!$this->output->confirm($this->getConfirmationQuestion($environment), !$this->input->isInteractive())) {
            return;
        }

        $this->apiClient->changeEnvironmentVariables($this->getProject(), $environment, [
            'YMIR_MAINTENANCE_MODE' => $this->getMaintenanceMode(),
        ]);

        $redeployment = $this->apiClient->createRedeployment($this->getProject(), $environment);

        if (!$redeployment->getId()) {
            throw new DeploymentFailedException('There was an error creating the redeployment');
        }

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($this->getContext(), $redeployment, $environment);
        }

        $this->output->info($this->getSuccessMessage());
    }

    /**
     * Get the confirmation question for the resolved environment.
     */
    abstract protected function getConfirmationQuestion(Environment $environment): string;

    /**
     * Get the question to ask for the environment.
     */
    abstract protected function getEnvironmentQuestion(): string;

    /**
     * Get the maintenance mode variable value to request for the environment.
     */
    abstract protected function getMaintenanceMode(): string;

    /**
     * Get the message to display when the redeployment was successful.
     */
    abstract protected function getSuccessMessage(): string;

    /**
     * Add a deployment step to the command.
     */
    private function addDeploymentStep(DeploymentStepInterface $deploymentStep): void
    {
        $this->deploymentSteps[] = $deploymentStep;
    }
}
