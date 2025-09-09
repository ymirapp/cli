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

use Ymir\Cli\Resource\Model\Certificate;

class CertificateFactory
{
    public static function create(array $data = []): Certificate
    {
        $defaultProvider = [
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
        ];

        if (isset($data['provider'])) {
            $data['provider'] = array_merge($defaultProvider, $data['provider']);
        }

        return Certificate::fromArray(array_merge([
            'id' => 1,
            'region' => 'us-east-1',
            'status' => 'available',
            'in_use' => false,
            'domains' => [],
            'provider' => $defaultProvider,
        ], $data));
    }
}
