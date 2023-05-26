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

use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Support\Arr;

/**
 * Base command for invoking a project function.
 */
abstract class AbstractInvocationCommand extends AbstractProjectCommand
{
    /**
     * Invokes the environment console function with the given PHP command and returns the output.
     */
    protected function invokePhpCommand(string $command, string $environment, int $timeout = null): array
    {
        return $this->invokeEnvironmentFunction($environment, [
            'php' => $command,
        ], $timeout);
    }

    /**
     * Invokes the environment console function with the given WP-CLI command and returns the output.
     */
    protected function invokeWpCliCommand(string $command, string $environment, int $timeout = null): array
    {
        if ('wp ' === substr($command, 0, 3)) {
            $command = substr($command, 3);
        }

        return $this->invokeEnvironmentFunction($environment, [
            'php' => sprintf('bin/wp %s', $command),
        ], $timeout);
    }

    /**
     * Invokes the given environment console function with the given payload and returns the output.
     */
    private function invokeEnvironmentFunction(string $environment, array $payload, int $timeout = null): array
    {
        $invocationId = $this->apiClient->createInvocation($this->projectConfiguration->getProjectId(), $environment, $payload)->get('id');

        if (!is_int($invocationId)) {
            throw new \RuntimeException('Unable to create command invocation');
        }

        if (0 === $timeout) {
            return [];
        } elseif (!is_int($timeout)) {
            $timeout = (int) Arr::get($this->projectConfiguration->getEnvironment($environment), 'console.timeout', 60);
        }

        $invocation = $this->wait(function () use ($invocationId) {
            $invocation = $this->apiClient->getInvocation($invocationId);

            return !in_array($invocation->get('status'), ['pending', 'running']) ? $invocation->all() : [];
        }, $timeout);

        if (empty($invocation['status']) || 'failed' === $invocation['status']) {
            throw new RuntimeException('Running the command failed');
        } elseif (!Arr::has($invocation, ['result.exitCode', 'result.output'])) {
            throw new RuntimeException('Unable to get the result of the command from the Ymir API');
        }

        return $invocation['result'];
    }
}
