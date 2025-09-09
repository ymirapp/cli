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

use Ymir\Cli\Resource\Model\Secret;
use Ymir\Cli\Tests\TestCase;

class SecretTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $secret = Secret::fromArray($this->getSecretData());

        $this->assertSame(1, $secret->getId());
    }

    public function testFromArraySetsName(): void
    {
        $secret = Secret::fromArray($this->getSecretData());

        $this->assertSame('name', $secret->getName());
    }

    public function testFromArraySetsUpdatedAt(): void
    {
        $secret = Secret::fromArray($this->getSecretData());

        $this->assertSame('updated_at', $secret->getUpdatedAt());
    }

    public function testGetId(): void
    {
        $secret = new Secret(1, 'name', 'updated_at');

        $this->assertSame(1, $secret->getId());
    }

    public function testGetName(): void
    {
        $secret = new Secret(1, 'name', 'updated_at');

        $this->assertSame('name', $secret->getName());
    }

    public function testGetUpdatedAt(): void
    {
        $secret = new Secret(1, 'name', 'updated_at');

        $this->assertSame('updated_at', $secret->getUpdatedAt());
    }

    private function getSecretData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'updated_at' => 'updated_at',
        ];
    }
}
