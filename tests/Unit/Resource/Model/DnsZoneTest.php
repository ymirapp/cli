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
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class DnsZoneTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $dnsZone = DnsZone::fromArray($this->getDnsZoneData());

        $this->assertSame(1, $dnsZone->getId());
    }

    public function testFromArraySetsName(): void
    {
        $dnsZone = DnsZone::fromArray($this->getDnsZoneData());

        $this->assertSame('domain.com', $dnsZone->getName());
    }

    public function testFromArraySetsNameServers(): void
    {
        $dnsZone = DnsZone::fromArray($this->getDnsZoneData());

        $this->assertSame(['ns1', 'ns2'], $dnsZone->getNameServers());
    }

    public function testFromArraySetsProvider(): void
    {
        $dnsZone = DnsZone::fromArray($this->getDnsZoneData());

        $this->assertSame(2, $dnsZone->getProvider()->getId());
    }

    public function testGetId(): void
    {
        $dnsZone = $this->createDnsZone();

        $this->assertSame(1, $dnsZone->getId());
    }

    public function testGetName(): void
    {
        $dnsZone = $this->createDnsZone();

        $this->assertSame('domain.com', $dnsZone->getName());
    }

    public function testGetNameServers(): void
    {
        $dnsZone = $this->createDnsZone();

        $this->assertSame(['ns1', 'ns2'], $dnsZone->getNameServers());
    }

    public function testGetProvider(): void
    {
        $dnsZone = $this->createDnsZone();

        $this->assertInstanceOf(CloudProvider::class, $dnsZone->getProvider());
    }

    private function createDnsZone(): DnsZone
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);

        return new DnsZone(1, 'domain.com', $provider, ['ns1', 'ns2']);
    }

    private function getDnsZoneData(): array
    {
        return [
            'id' => 1,
            'domain_name' => 'domain.com',
            'name_servers' => ['ns1', 'ns2'],
            'provider' => [
                'id' => 2,
                'name' => 'provider',
                'team' => [
                    'id' => 3,
                    'name' => 'team',
                    'owner' => [
                        'id' => 4,
                        'name' => 'owner',
                    ],
                ],
            ],
        ];
    }
}
