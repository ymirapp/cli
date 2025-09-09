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

use Ymir\Cli\Resource\Model\Deployment;

class DeploymentFactory
{
    public static function create(array $data = []): Deployment
    {
        return Deployment::fromArray(array_merge([
            'id' => 1,
            'uuid' => 'uuid',
            'status' => 'pending',
            'created_at' => 'now',
            'configuration' => [],
            'unmanaged_domains' => [],
            'type' => 'zip',
            'assets_hash' => 'hash',
        ], $data));
    }
}
