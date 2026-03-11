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

trait ParsesConsoleCommandTrait
{
    /**
     * Parses a command string into command parts.
     */
    protected function parseCommand(string $command): array
    {
        return collect(preg_split('/\s+/', trim($command)) ?: [])
            ->filter(function (string $commandPart): bool {
                return '' !== $commandPart;
            })
            ->values()
            ->all();
    }

    /**
     * Removes the first matching command prefix from a command string.
     */
    protected function stripCommandPrefix(string $command, $prefixes): string
    {
        $prefix = collect((array) $prefixes)->first(function (string $prefix) use ($command): bool {
            return str_starts_with($command, sprintf('%s ', $prefix));
        });

        return is_string($prefix) ? substr($command, strlen(sprintf('%s ', $prefix))) : $command;
    }
}
