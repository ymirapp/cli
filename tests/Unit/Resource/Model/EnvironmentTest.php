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

use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function testFromArraySetsContentDeliveryNetwork(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame(['cdn'], $environment->getContentDeliveryNetwork());
    }

    public function testFromArraySetsGateway(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame(['gateway'], $environment->getGateway());
    }

    public function testFromArraySetsId(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame(1, $environment->getId());
    }

    public function testFromArraySetsName(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame('name', $environment->getName());
    }

    public function testFromArraySetsPublicStoreDomainName(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame('public', $environment->getPublicStoreDomainName());
    }

    public function testFromArraySetsVanityDomainName(): void
    {
        $environment = Environment::fromArray($this->getEnvironmentData());

        $this->assertSame('vanity', $environment->getVanityDomainName());
    }

    public function testGetContentDeliveryNetwork(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame(['cdn'], $environment->getContentDeliveryNetwork());
    }

    public function testGetGateway(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame(['gateway'], $environment->getGateway());
    }

    public function testGetId(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame(1, $environment->getId());
    }

    public function testGetName(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame('name', $environment->getName());
    }

    public function testGetPublicStoreDomainName(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame('public', $environment->getPublicStoreDomainName());
    }

    public function testGetVanityDomainName(): void
    {
        $environment = new Environment(1, 'name', 'vanity', ['gateway'], ['cdn'], 'public');

        $this->assertSame('vanity', $environment->getVanityDomainName());
    }

    private function getEnvironmentData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'vanity_domain_name' => 'vanity',
            'gateway' => ['gateway'],
            'content_delivery_network' => ['cdn'],
            'public_store_domain_name' => 'public',
        ];
    }
}
