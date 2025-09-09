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

namespace Ymir\Cli\Tests\Unit\Resource\Model;

use Ymir\Cli\Resource\Model\BastionHost;
use Ymir\Cli\Tests\TestCase;

class BastionHostTest extends TestCase
{
    public function testFromArraySetsEndpoint(): void
    {
        $bastionHost = BastionHost::fromArray($this->getBastionHostData());

        $this->assertSame('endpoint', $bastionHost->getEndpoint());
    }

    public function testFromArraySetsId(): void
    {
        $bastionHost = BastionHost::fromArray($this->getBastionHostData());

        $this->assertSame(1, $bastionHost->getId());
    }

    public function testFromArraySetsName(): void
    {
        $bastionHost = BastionHost::fromArray($this->getBastionHostData());

        $this->assertSame('key_name', $bastionHost->getName());
    }

    public function testFromArraySetsPrivateKey(): void
    {
        $bastionHost = BastionHost::fromArray($this->getBastionHostData());

        $this->assertSame('private_key', $bastionHost->getPrivateKey());
    }

    public function testFromArraySetsStatus(): void
    {
        $bastionHost = BastionHost::fromArray($this->getBastionHostData());

        $this->assertSame('status', $bastionHost->getStatus());
    }

    public function testGetEndpoint(): void
    {
        $bastionHost = new BastionHost(1, 'key_name', 'endpoint', 'private_key', 'status');

        $this->assertSame('endpoint', $bastionHost->getEndpoint());
    }

    public function testGetId(): void
    {
        $bastionHost = new BastionHost(1, 'key_name', 'endpoint', 'private_key', 'status');

        $this->assertSame(1, $bastionHost->getId());
    }

    public function testGetName(): void
    {
        $bastionHost = new BastionHost(1, 'key_name', 'endpoint', 'private_key', 'status');

        $this->assertSame('key_name', $bastionHost->getName());
    }

    public function testGetPrivateKey(): void
    {
        $bastionHost = new BastionHost(1, 'key_name', 'endpoint', 'private_key', 'status');

        $this->assertSame('private_key', $bastionHost->getPrivateKey());
    }

    public function testGetStatus(): void
    {
        $bastionHost = new BastionHost(1, 'key_name', 'endpoint', 'private_key', 'status');

        $this->assertSame('status', $bastionHost->getStatus());
    }

    private function getBastionHostData(): array
    {
        return [
            'id' => 1,
            'key_name' => 'key_name',
            'endpoint' => 'endpoint',
            'private_key' => 'private_key',
            'status' => 'status',
        ];
    }
}
