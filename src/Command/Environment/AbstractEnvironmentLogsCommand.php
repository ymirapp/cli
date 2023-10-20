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

namespace Ymir\Cli\Command\Environment;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidTimeZoneException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Output;

abstract class AbstractEnvironmentLogsCommand extends AbstractProjectCommand
{
    /**
     * Write the logs to the console output.
     */
    protected function writeLogs(Collection $logs, Output $output, string $timezone = null)
    {
        $logs->each(function (array $log) use ($timezone, $output) {
            $timestamp = Carbon::createFromTimestamp($log['timestamp'] / 1000);

            if ($timezone) {
                try {
                    $timestamp->setTimezone($timezone);
                } catch (InvalidTimeZoneException $exception) {
                    throw new InvalidArgumentException(sprintf('"%s" is not a valid timezone', $timezone));
                }
            }

            $output->writeln(sprintf('<comment>[%s]</comment> %s', $timestamp->toDateTimeString(), trim($log['message'])));
        });
    }
}
