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

use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class CloudProviderTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $provider = CloudProvider::fromArray($this->getCloudProviderData());

        $this->assertSame(1, $provider->getId());
    }

    public function testFromArraySetsName(): void
    {
        $provider = CloudProvider::fromArray($this->getCloudProviderData());

        $this->assertSame('name', $provider->getName());
    }

    public function testFromArraySetsTeam(): void
    {
        $provider = CloudProvider::fromArray($this->getCloudProviderData());

        $this->assertSame(2, $provider->getTeam()->getId());
    }

    public function testGetId(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'name', $team);

        $this->assertSame(1, $provider->getId());
    }

    public function testGetName(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'name', $team);

        $this->assertSame('name', $provider->getName());
    }

    public function testGetTeam(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'name', $team);

        $this->assertSame($team, $provider->getTeam());
    }

    private function getCloudProviderData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'team' => [
                'id' => 2,
                'name' => 'team',
                'owner' => [
                    'id' => 3,
                    'name' => 'owner',
                ],
            ],
        ];
    }
}
