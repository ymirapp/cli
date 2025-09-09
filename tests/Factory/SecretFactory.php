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

use Ymir\Cli\Resource\Model\Secret;

class SecretFactory
{
    public static function create(array $data = []): Secret
    {
        return Secret::fromArray(array_merge([
            'id' => 1,
            'name' => 'secret',
            'updated_at' => 'now',
        ], $data));
    }
}
