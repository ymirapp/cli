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

use Ymir\Cli\Resource\Model\EmailIdentity;

class EmailIdentityFactory
{
    public static function create(array $data = []): EmailIdentity
    {
        return EmailIdentity::fromArray(array_merge([
            'id' => 1,
            'name' => 'example.com',
            'region' => 'us-east-1',
            'provider' => [
                'id' => 1,
                'name' => 'provider',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => [
                        'id' => 1,
                        'name' => 'owner',
                    ],
                ],
            ],
            'type' => 'domain',
            'verified' => true,
            'managed' => false,
            'dkim_authentication_records' => [],
        ], $data));
    }
}
