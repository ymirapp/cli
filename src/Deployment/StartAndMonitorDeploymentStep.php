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

namespace Placeholder\Cli\Deployment;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Exception\RuntimeException;
use Tightenco\Collect\Support\Collection;

class StartAndMonitorDeploymentStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the placeholder API.
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
    public function perform(int $deploymentId, OutputStyle $output)
    {
        $output->info('Deployment starting');

        $this->registerCancellationHandler($deploymentId, $output);

        $this->apiClient->startDeployment($deploymentId);

        $this->waitForDeploymentStatusChange($deploymentId, 'pending');

        $steps = $this->getDeploymentSteps($deploymentId);

        foreach ($steps as $step) {
            $output->writeStep($this->getFormattedDeploymentStepName($step['job']));
            $this->waitForDeploymentStepToFinish($deploymentId, $step['id']);
        }
    }

    /**
     * Get the deployment steps for the given deployment from the placeholder API.
     */
    private function getDeploymentSteps(int $deploymentId): Collection
    {
        return collect($this->apiClient->getDeployment($deploymentId)->get('steps'))->keyBy('id');
    }

    /**
     * Get the formatted deployment step name from the job name.
     */
    private function getFormattedDeploymentStepName(string $jobName): string
    {
        return ucfirst(strtr(strtolower((string) preg_replace(['/(.)(?=[A-Z])/u', '/Job$/'], ['$1 ', ''], $jobName)), [
            'api' => 'API',
            'assign' => 'assigning',
            'create' => 'creating',
            'configure' => 'configuring',
            'cors' => 'CORS',
            'ensure' => 'ensuring',
            'rest' => 'REST',
            'set' => 'setting',
            'ssl' => 'SSL',
            'update' => 'updating',
        ]));
    }

    /**
     * Register a signal handler to handle deployment cancellation.
     */
    private function registerCancellationHandler(int $deploymentId, OutputStyle $output)
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () use ($deploymentId, $output) {
            $output->newLine();
            $output->warn('Attempting to cancel the deployment');

            $this->apiClient->cancelDeployment($deploymentId);
            $this->waitForDeploymentStatusChange($deploymentId, 'cancelling');

            $output->info('Deployment cancelled successfully');

            exit;
        });
    }

    /**
     * Blocking method that constantly queries the placeholder API to see if the deployment status
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
     * Blocking method that constantly queries the placeholder API to see if the deployment step finished.
     */
    private function waitForDeploymentStepToFinish(int $deploymentId, int $deploymentStepId, int $timeout = 240)
    {
        $elapsed = 0;

        do {
            if ($elapsed > $timeout) {
                throw new RuntimeException('Timeout waiting for deployment step to finish');
            }

            $step = $this->getDeploymentSteps($deploymentId)->get($deploymentStepId);

            if (empty($step['status'])) {
                throw new RuntimeException('Unable to get deployment status from placeholder API');
            } elseif ('failed' === $step['status']) {
                throw new RuntimeException('Deployment failed');
            }

            ++$elapsed;
            sleep(1);
        } while ('finished' !== $step['status']);
    }
}
