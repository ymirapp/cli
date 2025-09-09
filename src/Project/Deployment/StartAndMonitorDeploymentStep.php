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

use Illuminate\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;

class StartAndMonitorDeploymentStep implements DeploymentStepInterface
{
    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment): void
    {
        $apiClient = $context->getApiClient();
        $output = $context->getOutput();

        $output->info(sprintf('%s starting', ucfirst($deployment->getType())));

        $this->registerCancellationHandler($context, $deployment);

        $apiClient->startDeployment($deployment);

        $this->waitForDeploymentStatusChange($context, $deployment, 'pending');

        $this->getDeploymentSteps($apiClient, $deployment)->each(function (array $step) use ($apiClient, $deployment, $output): void {
            $output->writeStep($this->getFormattedDeploymentStepName($step['task']));
            $this->waitForDeploymentStepToFinish($apiClient, $deployment, $step['id']);
        });
    }

    /**
     * Get the deployment steps for the given deployment from the Ymir API.
     */
    private function getDeploymentSteps(ApiClient $apiClient, Deployment $deployment): Collection
    {
        return collect($apiClient->getDeployment($deployment->getId())->getSteps())->keyBy('id');
    }

    /**
     * Get the exception message when a deployment failed.
     */
    private function getFailedDeploymentMessage(ApiClient $apiClient, Deployment $deployment): string
    {
        $failedMessage = $apiClient->getDeployment($deployment->getId())->getFailedMessage();
        $message = 'Deployment failed';

        if (!empty($failedMessage)) {
            $message .= " with error message:\n\n\t".$failedMessage;
        }

        return $message;
    }

    /**
     * Get the formatted deployment step name from the job name.
     */
    private function getFormattedDeploymentStepName(string $jobName): string
    {
        return ucfirst(strtr(strtolower((string) preg_replace(['/(.)(?=[A-Z])/u', '/(Job|Task)$/'], ['$1 ', ''], $jobName)), [
            'api' => 'API',
            'assign' => 'assigning',
            'clear' => 'clearing',
            'create' => 'creating',
            'configure' => 'configuring',
            'cors' => 'CORS',
            'dns' => 'DNS',
            'ensure' => 'ensuring',
            'execute' => 'executing',
            'http' => 'HTTP',
            'prepare' => 'preparing',
            'rest' => 'REST',
            'set' => 'setting',
            'ssl' => 'SSL',
            'update' => 'updating',
            'warm' => 'warming',
        ]));
    }

    /**
     * Register a signal handler to handle deployment cancellation.
     */
    private function registerCancellationHandler(ExecutionContext $context, Deployment $deployment): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () use ($context, $deployment): void {
            $output = $context->getOutput();
            $output->newLine();
            $output->comment(sprintf('Attempting to cancel the %s', $deployment->getType()));

            $context->getApiClient()->cancelDeployment($deployment);
            $this->waitForDeploymentStatusChange($context, $deployment, 'cancelling');

            $output->info(sprintf('%s cancelled', ucfirst($deployment->getType())));

            exit;
        });
    }

    /**
     * Blocking method that constantly queries the Ymir API to see if the deployment status
     * changed from the given status.
     */
    private function waitForDeploymentStatusChange(ExecutionContext $context, Deployment $deployment, string $status, int $timeout = 60): void
    {
        $elapsed = 0;

        do {
            if ($elapsed > $timeout) {
                throw new DeploymentFailedException('Timeout waiting for deployment status to change');
            }

            $deployment = $context->getApiClient()->getDeployment($deployment->getId());

            ++$elapsed;
            sleep(1);
        } while ($status === $deployment->getStatus());
    }

    /**
     * Blocking method that constantly queries the Ymir API to see if the deployment step finished.
     */
    private function waitForDeploymentStepToFinish(ApiClient $apiClient, Deployment $deployment, int $deploymentStepId, int $timeout = 600): void
    {
        $elapsed = 0;

        do {
            if ($elapsed > $timeout) {
                throw new DeploymentFailedException('Timeout waiting for deployment step to finish');
            }

            $step = $this->getDeploymentSteps($apiClient, $deployment)->get($deploymentStepId);

            if (empty($step['status'])) {
                throw new DeploymentFailedException('Unable to get deployment status from Ymir API');
            } elseif ('failed' === $step['status']) {
                throw new DeploymentFailedException($this->getFailedDeploymentMessage($apiClient, $deployment));
            }

            ++$elapsed;
            sleep(1);
        } while ('finished' !== $step['status']);
    }
}
