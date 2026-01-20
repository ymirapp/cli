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

namespace Ymir\Cli\Command;

use Ymir\Cli\Exception\InvocationException;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Support\Arr;

/**
 * Trait for commands that invoke a project function.
 */
trait HandlesInvocationTrait
{
    /**
     * Invokes the environment console function with the given PHP command and returns the output.
     */
    protected function invokePhpCommand(Project $project, string $command, Environment $environment, ?int $timeout = null): array
    {
        return $this->invokeEnvironmentFunction($project, $environment, [
            'php' => $command,
        ], $timeout);
    }

    /**
     * Invokes the given environment console function with the given payload and returns the output.
     */
    private function invokeEnvironmentFunction(Project $project, Environment $environment, array $payload, ?int $timeout = null): array
    {
        $invocationId = $this->apiClient->createInvocation($project, $environment, $payload)->get('id');

        if (!is_int($invocationId)) {
            throw new InvocationException('Unable to create command invocation');
        }

        if (0 === $timeout) {
            return [];
        } elseif (!is_int($timeout)) {
            $timeout = $this->getProjectConfiguration()->getEnvironmentConfiguration($environment->getName())->getConsoleTimeout();
        }

        $invocation = $this->wait(function () use ($invocationId) {
            $invocation = $this->apiClient->getInvocation($invocationId);

            return !in_array($invocation->get('status'), ['pending', 'running']) ? $invocation->all() : [];
        }, $timeout);

        if (empty($invocation['status']) || 'failed' === $invocation['status']) {
            throw new InvocationException('Running the command failed');
        } elseif (!Arr::has($invocation, ['result.exitCode', 'result.output'])) {
            throw new InvocationException('Unable to get the result of the command from the Ymir API');
        }

        return $invocation['result'];
    }
}
