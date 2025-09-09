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

use Ymir\Cli\Resource\Model\Network;
use Ymir\Cli\Tests\TestCase;

class NetworkTest extends TestCase
{
    public function testGetBastionHost(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame(5, $network->getBastionHost()->getId());
    }

    public function testGetId(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame(1, $network->getId());
    }

    public function testGetName(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame('name', $network->getName());
    }

    public function testGetProvider(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame(2, $network->getProvider()->getId());
    }

    public function testGetRegion(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame('region', $network->getRegion());
    }

    public function testGetStatus(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertSame('status', $network->getStatus());
    }

    public function testHasNatGateway(): void
    {
        $network = Network::fromArray($this->getNetworkData());

        $this->assertTrue($network->hasNatGateway());
    }

    private function getNetworkData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'status' => 'status',
            'has_nat_gateway' => true,
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
            'bastion_host' => [
                'id' => 5,
                'key_name' => 'key_name',
                'endpoint' => 'endpoint',
                'private_key' => 'private_key',
                'status' => 'status',
            ],
        ];
    }
}
