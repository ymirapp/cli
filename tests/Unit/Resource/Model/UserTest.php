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

use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class UserTest extends TestCase
{
    public function testFromArraySetsEmail(): void
    {
        $user = User::fromArray($this->getUserData());

        $this->assertSame('email', $user->getEmail());
    }

    public function testFromArraySetsId(): void
    {
        $user = User::fromArray($this->getUserData());

        $this->assertSame(1, $user->getId());
    }

    public function testFromArraySetsName(): void
    {
        $user = User::fromArray($this->getUserData());

        $this->assertSame('name', $user->getName());
    }

    public function testGetEmail(): void
    {
        $user = new User(1, 'name', 'email');

        $this->assertSame('email', $user->getEmail());
    }

    public function testGetId(): void
    {
        $user = new User(1, 'name', 'email');

        $this->assertSame(1, $user->getId());
    }

    public function testGetName(): void
    {
        $user = new User(1, 'name', 'email');

        $this->assertSame('name', $user->getName());
    }

    private function getUserData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'email' => 'email',
        ];
    }
}
