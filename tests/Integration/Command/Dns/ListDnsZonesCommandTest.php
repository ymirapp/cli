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

use Ymir\Cli\Command\Dns\ListDnsZonesCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListDnsZonesCommandTest extends TestCase
{
    public function testListDnsZonesSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create([
            'id' => 10,
            'domain_name' => 'example.com',
            'name_servers' => ['ns1.example.com', 'ns2.example.com'],
            'provider' => ['id' => 1, 'name' => 'AWS'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));

        $this->bootApplication([new ListDnsZonesCommand($this->apiClient, $this->createExecutionContextFactory())]);
        $tester = $this->executeCommand(ListDnsZonesCommand::NAME);

        $this->assertStringContainsString('10', $tester->getDisplay());
        $this->assertStringContainsString('AWS', $tester->getDisplay());
        $this->assertStringContainsString('example.com', $tester->getDisplay());
        $this->assertStringContainsString('ns1.example.com', $tester->getDisplay());
        $this->assertStringContainsString('ns2.example.com', $tester->getDisplay());
    }
}
