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

/**
 * Base command for invoking a project function.
 */
abstract class AbstractInvocationCommand extends AbstractProjectCommand
{
    /**
     * Invokes the given environment console function with the given payload and returns the output.
     */
    protected function invokeEnvironmentFunction(string $environment, array $payload): array
    {
        $invocation = $this->apiClient->createInvocation($this->projectConfiguration->getProjectId(), $environment, $payload);

        while (empty($invocation['status']) || in_array($invocation['status'], ['pending', 'running'])) {
            sleep(1);

            $invocation = $this->apiClient->getInvocation($invocation['id']);
        }

        if ('failed' === $invocation['status']) {
            throw new RuntimeException('Running the command failed');
        }

        if (!isset($invocation['result']['exitCode'], $invocation['result']['output'])) {
            throw new RuntimeException('Unable to get the result of the command from the Ymir API');
        }

        return $invocation['result'];
    }
}
