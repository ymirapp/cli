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

        $this->apiClient->startDeployment($deploymentId);

        $this->waitForDeploymentToStart($deploymentId);

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
        return str_replace(
            ['Ensure', 'Update'],
            ['Ensuring', 'Updating'],
            ucfirst(strtolower((string) preg_replace(['/(.)(?=[A-Z])/u', '/Job$/'], ['$1 ', ''], $jobName)))
        );
    }

    /**
     * Blocking method that constantly queries the placeholder API to see if the deployment step finished.
     */
    private function waitForDeploymentStepToFinish(int $deploymentId, int $deploymentStepId)
    {
        $elapsed = 0;
        $timeout = 240;

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

    /**
     * Blocking method that constantly queries the placeholder API to see if the deployment started.
     */
    private function waitForDeploymentToStart(int $deploymentId)
    {
        $elapsed = 0;
        $timeout = 60;

        do {
            if ($elapsed > $timeout) {
                throw new RuntimeException('Timeout waiting for deployment to start');
            }

            $deployment = $this->apiClient->getDeployment($deploymentId);

            ++$elapsed;
            sleep(1);
        } while ('pending' === $deployment['status']);
    }
}
