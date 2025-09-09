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

use Ymir\Cli\Resource\Model\DnsRecord;
use Ymir\Cli\Tests\TestCase;

class DnsRecordTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $dnsRecord = DnsRecord::fromArray($this->getDnsRecordData());

        $this->assertSame(1, $dnsRecord->getId());
    }

    public function testFromArraySetsIsInternal(): void
    {
        $dnsRecord = DnsRecord::fromArray($this->getDnsRecordData());

        $this->assertTrue($dnsRecord->isInternal());
    }

    public function testFromArraySetsName(): void
    {
        $dnsRecord = DnsRecord::fromArray($this->getDnsRecordData());

        $this->assertSame('name', $dnsRecord->getName());
    }

    public function testFromArraySetsType(): void
    {
        $dnsRecord = DnsRecord::fromArray($this->getDnsRecordData());

        $this->assertSame('type', $dnsRecord->getType());
    }

    public function testFromArraySetsValue(): void
    {
        $dnsRecord = DnsRecord::fromArray($this->getDnsRecordData());

        $this->assertSame('value', $dnsRecord->getValue());
    }

    public function testGetId(): void
    {
        $dnsRecord = new DnsRecord(1, 'name', 'type', 'value', true);

        $this->assertSame(1, $dnsRecord->getId());
    }

    public function testGetName(): void
    {
        $dnsRecord = new DnsRecord(1, 'name', 'type', 'value', true);

        $this->assertSame('name', $dnsRecord->getName());
    }

    public function testGetType(): void
    {
        $dnsRecord = new DnsRecord(1, 'name', 'type', 'value', true);

        $this->assertSame('type', $dnsRecord->getType());
    }

    public function testGetValue(): void
    {
        $dnsRecord = new DnsRecord(1, 'name', 'type', 'value', true);

        $this->assertSame('value', $dnsRecord->getValue());
    }

    public function testIsInternal(): void
    {
        $dnsRecord = new DnsRecord(1, 'name', 'type', 'value', true);

        $this->assertTrue($dnsRecord->isInternal());
    }

    private function getDnsRecordData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'type' => 'type',
            'value' => 'value',
            'internal' => true,
        ];
    }
}
