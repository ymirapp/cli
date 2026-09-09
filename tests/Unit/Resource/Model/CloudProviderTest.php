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

use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class CloudProviderTest extends TestCase
{
    public static function provideAuthenticationMethods(): array
    {
        return [[null], ['access_key'], ['assume_role']];
    }

    public static function provideMetadata(): array
    {
        return [
            'pending' => [['status' => 'pending', 'authentication' => ['method' => null]], 'pending', null],
            'access key' => [['status' => 'connected', 'authentication' => ['method' => 'access_key']], 'connected', 'access_key'],
            'assume role' => [['status' => 'connected', 'authentication' => ['method' => 'assume_role']], 'connected', 'assume_role'],
            'disconnected' => [['status' => 'disconnected', 'authentication' => ['method' => 'assume_role']], 'disconnected', 'assume_role'],
            'missing authentication' => [[], 'connected', null],
            'missing method' => [['status' => 'pending', 'authentication' => []], 'pending', null],
            'null authentication' => [['authentication' => null], 'connected', null],
            'null setup' => [['authentication' => ['method' => 'access_key', 'assume_role' => null]], 'connected', 'access_key'],
        ];
    }

    /**
     * @dataProvider provideAuthenticationMethods
     */
    public function testConstructorAcceptsAuthenticationNode(?string $method): void
    {
        $provider = new CloudProvider(1, 'name', new Team(2, 'team', new User(3, 'owner')), 'pending', [
            'method' => $method,
            'assume_role' => [
                'ymir_account_id' => '012345678901',
                'external_id' => 'ymir-external-id',
                'role_name' => 'ymir-cloud-provider-1',
            ],
        ]);

        $this->assertSame($method, $provider->getAuthenticationMethod());
    }

    public function testConstructorWithoutAuthentication(): void
    {
        $provider = new CloudProvider(1, 'name', new Team(2, 'team', new User(3, 'owner')), 'connected');

        $this->assertSame('connected', $provider->getStatus());
        $this->assertNull($provider->getAuthenticationMethod());
    }

    /**
     * @dataProvider provideMetadata
     */
    public function testFromArrayPreservesMetadata(array $metadata, string $status, ?string $method): void
    {
        $provider = CloudProvider::fromArray(array_merge($this->getCloudProviderData(), $metadata));

        $this->assertSame(1, $provider->getId());
        $this->assertSame('name', $provider->getName());
        $this->assertSame(2, $provider->getTeam()->getId());
        $this->assertSame($status, $provider->getStatus());
        $this->assertSame($method, $provider->getAuthenticationMethod());
    }

    public function testFromArrayRequiresStatus(): void
    {
        $data = $this->getCloudProviderData();
        unset($data['status']);

        $this->expectException(InvalidArgumentException::class);
        CloudProvider::fromArray($data);
    }

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
        $provider = new CloudProvider(1, 'name', $team, 'connected');

        $this->assertSame(1, $provider->getId());
    }

    public function testGetName(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'name', $team, 'connected');

        $this->assertSame('name', $provider->getName());
    }

    public function testGetTeam(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'name', $team, 'connected');

        $this->assertSame($team, $provider->getTeam());
    }

    private function getCloudProviderData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'status' => 'connected',
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
