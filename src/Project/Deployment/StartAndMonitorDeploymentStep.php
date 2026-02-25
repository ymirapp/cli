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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class StartAndMonitorDeploymentStep implements DeploymentStepInterface
{
    /**
     * The list of deployment types and the verbs to use with them.
     */
    private const TYPE_VERBS = [
        'deployment' => 'Deploying',
        'redeployment' => 'Redeploying',
        'rollback' => 'Rolling back',
    ];

    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment): void
    {
        $project = $context->getProject();

        if (!$project instanceof Project) {
            throw new LogicException('No project found in the current context');
        }

        $apiClient = $context->getApiClient();
        $output = $context->getOutput();

        $output->info(sprintf('%s <comment>%s</comment> to <comment>%s</comment>', self::TYPE_VERBS[$deployment->getType()], $project->getName(), $environment->getName()));

        $this->registerCancellationHandler($context, $deployment);

        $apiClient->startDeployment($deployment);

        $printedStepIds = [];

        $this->waitForDeploymentToTerminate($apiClient, $deployment, function (Deployment $deployment) use ($apiClient, $output, &$printedStepIds): void {
            if ($deployment->hasFailed()) {
                throw new DeploymentFailedException($this->getFailedDeploymentMessage($apiClient, $deployment));
            }

            collect($deployment->getSteps())
                ->filter(function (array $step) use ($printedStepIds): bool {
                    return isset($step['id'], $step['task']) && !in_array($step['id'], $printedStepIds, true);
                })
                ->each(function (array $step) use ($output, &$printedStepIds): void {
                    $output->writeStep($this->getFormattedDeploymentStepName($step['task']));
                    $printedStepIds[] = $step['id'];
                });
        });
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

            $deployment = $this->waitForDeploymentToTerminate($context->getApiClient(), $deployment);

            $output->info(sprintf('%s cancelled', ucfirst($deployment->getType())));

            exit;
        });
    }

    /**
     * Wait for the deployment to reach a terminal status.
     */
    private function waitForDeploymentToTerminate(ApiClient $apiClient, Deployment $deployment, ?callable $onPoll = null, int $sleep = 2, ?int $timeout = 600): Deployment
    {
        $startTime = time();

        while (!$deployment->hasTerminated()) {
            if (null !== $timeout && time() - $startTime > $timeout) {
                throw new DeploymentFailedException('Timeout waiting for deployment to terminate');
            }

            $deployment = $apiClient->getDeployment($deployment->getId());

            if (is_callable($onPoll)) {
                $onPoll($deployment);
            }

            if (!$deployment->hasTerminated()) {
                sleep($sleep);
            }
        }

        return $deployment;
    }
}
