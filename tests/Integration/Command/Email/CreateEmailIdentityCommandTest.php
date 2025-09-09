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

namespace Ymir\Cli\Tests\Integration\Command\Email;

use Illuminate\Support\Collection;
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\EmailIdentityFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class CreateEmailIdentityCommandTest extends TestCase
{
    public function testCreateEmailIdentityDomain(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'aws']);
        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
            'type' => 'domain',
            'dkim_authentication_records' => [
                ['name' => 'selector1._domainkey.example.com', 'type' => 'cname', 'value' => 'selector1.dkim.amazonses.com'],
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createEmailIdentity')->with($provider, 'example.com', 'us-east-1')->andReturn($identity);

        $this->bootApplication([new CreateEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () {
                return new CloudProviderDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(CreateEmailIdentityCommand::NAME, [
            'name' => 'example.com',
            '--provider' => '1',
            '--region' => 'us-east-1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Email identity created', $display);
        $this->assertStringContainsString('The following DNS records needs to exist', $display);
        $this->assertStringContainsString('selector1._domainkey.example.com', $display);
    }

    public function testCreateEmailIdentityEmail(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'aws']);
        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'user@example.com',
            'type' => 'email',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createEmailIdentity')->with($provider, 'user@example.com', 'us-east-1')->andReturn($identity);

        $this->bootApplication([new CreateEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () {
                return new CloudProviderDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(CreateEmailIdentityCommand::NAME, [
            'name' => 'user@example.com',
            '--provider' => '1',
            '--region' => 'us-east-1',
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Email identity created', $display);
        $this->assertStringContainsString('A verification email was sent to user@example.com', $display);
    }

    public function testCreateEmailIdentityInteractive(): void
    {
        $team = $this->setupActiveTeam();

        $provider = CloudProviderFactory::create(['id' => 1, 'name' => 'aws']);
        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
            'type' => 'domain',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(new Collection(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createEmailIdentity')->with($provider, 'example.com', 'us-east-1')->andReturn($identity);

        $this->bootApplication([new CreateEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            CloudProvider::class => function () {
                return new CloudProviderDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(CreateEmailIdentityCommand::NAME, [], [
            'example.com', // Name
            '1',           // Provider
            'us-east-1',   // Region
        ]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Email identity created', $display);
    }
}
