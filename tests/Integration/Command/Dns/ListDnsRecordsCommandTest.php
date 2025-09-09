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

namespace Ymir\Cli\Tests\Integration\Command\Dns;

use Ymir\Cli\Command\Dns\ListDnsRecordsCommand;
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsRecordFactory;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListDnsRecordsCommandTest extends TestCase
{
    public function testListDnsRecordsPromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record]));

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new ListDnsRecordsCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListDnsRecordsCommand::NAME, [], ['example.com']);

        $this->assertStringContainsString('Which DNS zone would you like to list DNS records from?', $tester->getDisplay());
        $this->assertStringContainsString('www', $tester->getDisplay());
    }

    public function testListDnsRecordsSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record]));

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new ListDnsRecordsCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListDnsRecordsCommand::NAME, ['zone' => 'example.com']);

        $this->assertStringContainsString('100', $tester->getDisplay());
        $this->assertStringContainsString('www', $tester->getDisplay());
        $this->assertStringContainsString('A', $tester->getDisplay());
        $this->assertStringContainsString('1.2.3.4', $tester->getDisplay());
    }
}
