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

use Ymir\Cli\Command\Dns\DeleteDnsRecordCommand;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsRecordFactory;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDnsRecordCommandTest extends TestCase
{
    public function testDeleteAllDnsRecordsSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record]));
        $this->apiClient->shouldReceive('deleteDnsRecord')->once()->with($zone, $record);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsRecordCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsRecordCommand::NAME, ['zone' => 'example.com'], ['yes', 'yes']);

        $this->assertStringContainsString('You are about to delete all DNS records', $tester->getDisplay());
        $this->assertStringContainsString('DNS record(s) deleted', $tester->getDisplay());
    }

    public function testDeleteDnsRecordFailsIfInternal(): void
    {
        $this->expectException(ResourceStateException::class);
        $this->expectExceptionMessage('DNS record "100" is internal and cannot be deleted');

        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4', 'internal' => true]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record]));

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsRecordCommand($this->apiClient, $contextFactory)]);
        $this->executeCommand(DeleteDnsRecordCommand::NAME, ['zone' => 'example.com', 'record' => '100'], ['yes']);
    }

    public function testDeleteDnsRecordSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record]));
        $this->apiClient->shouldReceive('deleteDnsRecord')->once()->with($zone, $record);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsRecordCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsRecordCommand::NAME, ['zone' => 'example.com', 'record' => '100'], ['yes']);

        $this->assertStringContainsString('DNS record(s) deleted', $tester->getDisplay());
    }

    public function testDeleteDnsRecordWithFiltersSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);
        $record1 = DnsRecordFactory::create(['id' => 100, 'name' => 'www', 'type' => 'A', 'value' => '1.2.3.4']);
        $record2 = DnsRecordFactory::create(['id' => 101, 'name' => 'api', 'type' => 'A', 'value' => '5.6.7.8']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('getDnsRecords')->with($zone)->andReturn(new ResourceCollection([$record1, $record2]));
        $this->apiClient->shouldReceive('deleteDnsRecord')->once()->with($zone, $record1);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsRecordCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsRecordCommand::NAME, ['zone' => 'example.com', '--name' => 'www'], ['yes']);

        $this->assertStringContainsString('DNS record(s) deleted', $tester->getDisplay());
    }
}
