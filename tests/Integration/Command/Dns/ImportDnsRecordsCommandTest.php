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

use Ymir\Cli\Command\Dns\ImportDnsRecordsCommand;
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ImportDnsRecordsCommandTest extends TestCase
{
    public function testImportDnsRecordsPromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('importDnsRecords')->once()->with($zone, ['www', 'api']);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new ImportDnsRecordsCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ImportDnsRecordsCommand::NAME, ['zone' => 'example.com'], ['www,api']);

        $this->assertStringContainsString('Please enter a comma-separated list', $tester->getDisplay());
        $this->assertStringContainsString('DNS records imported', $tester->getDisplay());
    }

    public function testImportDnsRecordsSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('importDnsRecords')->once()->with($zone, ['www', 'api']);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new ImportDnsRecordsCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ImportDnsRecordsCommand::NAME, ['zone' => 'example.com', 'subdomain' => ['www', 'api']]);

        $this->assertStringContainsString('DNS records imported', $tester->getDisplay());
    }
}
