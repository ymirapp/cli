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

namespace Ymir\Cli\Command\WordPress;

use Ymir\Cli\Command\HandlesInvocationTrait;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

trait HandlesWpCliInvocationTrait
{
    use HandlesInvocationTrait;

    /**
     * Invokes the environment console function with the given WP-CLI command and returns the output.
     */
    protected function invokeWpCliCommand(Project $project, string $command, Environment $environment, ?int $timeout = null): array
    {
        if (str_starts_with($command, 'wp ')) {
            $command = substr($command, 3);
        }

        return $this->invokeEnvironmentFunction($project, $environment, [
            'php' => sprintf('bin/wp %s', $command),
        ], $timeout);
    }
}
