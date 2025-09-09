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

namespace Ymir\Cli\Tests\Factory;

use Ymir\Cli\Resource\Model\BastionHost;

class BastionHostFactory
{
    public static function create(array $data = []): BastionHost
    {
        return BastionHost::fromArray(array_merge([
            'id' => 1,
            'key_name' => 'name',
            'endpoint' => '1.2.3.4',
            'private_key' => 'key',
            'status' => 'available',
        ], $data));
    }
}
