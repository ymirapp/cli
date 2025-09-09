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

use Ymir\Cli\Command\Dns\CreateDnsZoneCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateDnsZoneCommandTest extends TestCase
{
    public function testCreateDnsZoneCancelledAtCostConfirmation(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('createDnsZone');

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new CreateDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateDnsZoneCommand::NAME, ['name' => 'example.com'], ['1', 'no']);

        $this->assertStringNotContainsString('DNS zone created', $tester->getDisplay());
    }

    public function testCreateDnsZonePromptedSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $zone = DnsZoneFactory::create([
            'id' => 10,
            'domain_name' => 'example.com',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('createDnsZone')->once()->with($provider, 'example.com')->andReturn($zone);
        $this->apiClient->shouldReceive('getDnsZone')->with(10)->andReturn($zone);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new CreateDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateDnsZoneCommand::NAME, [], ['example.com', '1', 'yes', 'no', 'no']);

        $this->assertStringContainsString('What is the name of the domain', $tester->getDisplay());
        $this->assertStringContainsString('DNS zone created', $tester->getDisplay());
    }

    public function testCreateDnsZoneSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'AWS']);
        $zone = DnsZoneFactory::create([
            'id' => 10,
            'domain_name' => 'example.com',
            'name_servers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('createDnsZone')->once()->with($provider, 'example.com')->andReturn($zone);
        $this->apiClient->shouldReceive('getDnsZone')->with(10)->andReturn($zone);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new CreateDnsZoneCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(CreateDnsZoneCommand::NAME, ['name' => 'example.com'], ['1', 'yes', 'no', 'no']);

        $this->assertStringContainsString('DNS zone created', $tester->getDisplay());
        $this->assertStringContainsString('ns1.example.com', $tester->getDisplay());
    }
}
