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

use Ymir\Cli\Command\Dns\DeleteDnsZoneCommand;
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteDnsZoneCommandTest extends TestCase
{
    public function testDeleteDnsZoneCancelled(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldNotReceive('deleteDnsZone');

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsZoneCommand::NAME, ['zone' => '10'], ['no']);

        $this->assertStringNotContainsString('DNS zone deleted', $tester->getDisplay());
    }

    public function testDeleteDnsZonePromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('deleteDnsZone')->once()->with($zone);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsZoneCommand::NAME, [], ['example.com', 'yes']);

        $this->assertStringContainsString('Which DNS zone would you like to delete?', $tester->getDisplay());
        $this->assertStringContainsString('DNS zone deleted', $tester->getDisplay());
    }

    public function testDeleteDnsZoneSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('deleteDnsZone')->once()->with($zone);

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new DeleteDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteDnsZoneCommand::NAME, ['zone' => 'example.com'], ['yes']);

        $this->assertStringContainsString('DNS zone deleted', $tester->getDisplay());
    }
}
