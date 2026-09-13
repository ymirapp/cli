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

namespace Ymir\Cli\Support;

trait WaitsForResultTrait
{
    /**
     * Poll the given callable until it returns a non-empty result or the timeout expires.
     *
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return T
     */
    protected function wait(callable $callable, int $timeout = 60, int $sleep = 1)
    {
        if (0 !== $timeout) {
            $timeout += time();
        }

        do {
            $result = $callable();
            sleep($sleep);
        } while (empty($result) && time() < $timeout);

        return $result;
    }
}
