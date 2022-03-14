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

use Illuminate\Support\Arr as LaravelArrHelper;

class Arr extends LaravelArrHelper
{
    /**
     * Recursively removes duplicate values from an array.
     */
    public static function uniqueRecursive(array $array, int $flags = SORT_REGULAR): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::uniqueRecursive($value, $flags);
            }
        }

        return array_unique($array, $flags);
    }
}
