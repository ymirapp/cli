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

use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class TeamTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $team = Team::fromArray($this->getTeamData());

        $this->assertSame(1, $team->getId());
    }

    public function testFromArraySetsName(): void
    {
        $team = Team::fromArray($this->getTeamData());

        $this->assertSame('name', $team->getName());
    }

    public function testFromArraySetsOwner(): void
    {
        $team = Team::fromArray($this->getTeamData());

        $this->assertSame(2, $team->getOwner()->getId());
    }

    public function testGetId(): void
    {
        $owner = new User(2, 'owner');
        $team = new Team(1, 'name', $owner);

        $this->assertSame(1, $team->getId());
    }

    public function testGetName(): void
    {
        $owner = new User(2, 'owner');
        $team = new Team(1, 'name', $owner);

        $this->assertSame('name', $team->getName());
    }

    public function testGetOwner(): void
    {
        $owner = new User(2, 'owner');
        $team = new Team(1, 'name', $owner);

        $this->assertSame($owner, $team->getOwner());
    }

    private function getTeamData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'owner' => [
                'id' => 2,
                'name' => 'owner',
            ],
        ];
    }
}
