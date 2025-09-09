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

use Carbon\Carbon;
use Carbon\Exceptions\InvalidTimeZoneException;
use Illuminate\Support\Collection;
use Ymir\Cli\Exception\InvalidInputException;

/**
 * Trait for commands that write environment logs to the console.
 */
trait HandlesEnvironmentLogsTrait
{
    /**
     * Write the logs to the console output.
     */
    protected function writeLogs(Collection $logs, ?string $timezone = null)
    {
        $logs->each(function (array $log) use ($timezone): void {
            $timestamp = Carbon::createFromTimestamp($log['timestamp'] / 1000);

            if ($timezone) {
                try {
                    $timestamp->setTimezone($timezone);
                } catch (InvalidTimeZoneException $exception) {
                    throw new InvalidInputException(sprintf('"%s" is not a valid timezone', $timezone));
                }
            }

            $this->output->writeln(sprintf('<comment>[%s]</comment> %s', $timestamp->toDateTimeString(), trim($log['message'])));
        });
    }
}
