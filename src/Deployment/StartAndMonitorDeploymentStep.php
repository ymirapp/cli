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

namespace Ymir\Cli\Deployment;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;

class StartAndMonitorDeploymentStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, string $environment, Output $output)
    {
        $output->info(sprintf('%s starting', ucfirst($deployment->get('type', 'deployment'))));

        $this->registerCancellationHandler($deployment, $output);

        $this->apiClient->startDeployment((int) $deployment->get('id'));

        $this->waitForDeploymentStatusChange((int) $deployment->get('id'), 'pending');

        $this->getDeploymentSteps((int) $deployment->get('id'))->each(function (array $step) use ($deployment, $output) {
            $output->writeStep($this->getFormattedDeploymentStepName($step['task']));
            $this->waitForDeploymentStepToFinish((int) $deployment->get('id'), $step['id']);
        });
    }

    /**
     * Get the deployment steps for the given deployment from the Ymir API.
     */
    private function getDeploymentSteps(int $deploymentId): Collection
    {
        return collect($this->apiClient->getDeployment($deploymentId)->get('steps'))->keyBy('id');
    }

    /**
     * Get the exception message when a deployment failed.
     */
    private function getFailedDeploymentMessage(int $deploymentId): string
    {
        $failedMessage = $this->apiClient->getDeployment($deploymentId)->get('failed_message');
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
    private function registerCancellationHandler(Collection $deployment, Output $output)
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () use ($deployment, $output) {
            $output->newLine();
            $output->comment(sprintf('Attempting to cancel the %s', $deployment->get('type', 'deployment')));

            $this->apiClient->cancelDeployment((int) $deployment->get('id'));
            $this->waitForDeploymentStatusChange((int) $deployment->get('id'), 'cancelling');

            $output->info(sprintf('%s cancelled', ucfirst($deployment->get('type', 'deployment'))));

            exit;
        });
    }

    /**
     * Blocking method that constantly queries the Ymir API to see if the deployment status
     * changed from the given status.
     */
    private function waitForDeploymentStatusChange(int $deploymentId, string $status, int $timeout = 60)
    {
        $elapsed = 0;

        do {
            if ($elapsed > $timeout) {
                throw new RuntimeException('Timeout waiting for deployment status to change');
            }

            $deployment = $this->apiClient->getDeployment($deploymentId);

            ++$elapsed;
            sleep(1);
        } while ($status === $deployment['status']);
    }

    /**
     * Blocking method that constantly queries the Ymir API to see if the deployment step finished.
     */
    private function waitForDeploymentStepToFinish(int $deploymentId, int $deploymentStepId, int $timeout = 600)
    {
        $elapsed = 0;

        do {
            if ($elapsed > $timeout) {
                throw new RuntimeException('Timeout waiting for deployment step to finish');
            }

            $step = $this->getDeploymentSteps($deploymentId)->get($deploymentStepId);

            if (empty($step['status'])) {
                throw new RuntimeException('Unable to get deployment status from Ymir API');
            } elseif ('failed' === $step['status']) {
                throw new RuntimeException($this->getFailedDeploymentMessage($deploymentId));
            }

            ++$elapsed;
            sleep(1);
        } while ('finished' !== $step['status']);
    }
}
