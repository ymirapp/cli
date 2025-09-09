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

use Ymir\Cli\Command\Dns\ChangeDnsRecordCommand;
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\Model\DnsZone;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ChangeDnsRecordCommandTest extends TestCase
{
    public function testChangeDnsRecordSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $zone = DnsZoneFactory::create(['id' => 10, 'domain_name' => 'example.com']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getDnsZones')->with($team)->andReturn(new ResourceCollection([$zone]));
        $this->apiClient->shouldReceive('changeDnsRecord')->once()->with($zone, 'A', 'www', '1.2.3.4');

        $contextFactory = $this->createExecutionContextFactory([
            DnsZone::class => function () { return new DnsZoneDefinition(); },
        ]);

        $this->bootApplication([new ChangeDnsRecordCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ChangeDnsRecordCommand::NAME, ['zone' => 'example.com', 'type' => 'A', 'name' => 'www', 'value' => '1.2.3.4']);

        $this->assertStringContainsString('DNS record change applied', $tester->getDisplay());
    }
}
