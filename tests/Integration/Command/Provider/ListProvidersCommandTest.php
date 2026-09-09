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

namespace Ymir\Cli\Tests\Integration\Command\Provider;

use Ymir\Cli\Command\Provider\ListProvidersCommand;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListProvidersCommandTest extends TestCase
{
    public function testListProvidersEmpty(): void
    {
        $team = $this->setupActiveTeam();
        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection());

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListProvidersCommand::NAME);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('The following cloud provider connections belong to your team:', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/Id\s+Name\s+Status\s+Authentication/', $tester->getDisplay());
    }

    public function testListProvidersFailsIfNoTeamIsActive(): void
    {
        $this->apiClient->shouldNotReceive('getProviders');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You do not have a currently active team, but you can select a team using the "team:select" command');

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $this->executeCommand(ListProvidersCommand::NAME);
    }

    public function testListProvidersPropagatesApiErrors(): void
    {
        $team = $this->setupActiveTeam();
        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andThrow(new RuntimeException('Not authorized to list providers'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not authorized to list providers');

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $this->createExecutionContextFactory())]);
        $this->executeCommand(ListProvidersCommand::NAME);
    }

    public function testListProvidersSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $providers = new ResourceCollection([
            CloudProviderFactory::create(['id' => 123, 'name' => 'NoMethod']),
            CloudProviderFactory::create([
                'id' => 124,
                'name' => 'Pending',
                'status' => 'pending',
                'authentication' => [
                    'method' => null,
                    'assume_role' => [
                        'ymir_account_id' => '012345678901',
                        'external_id' => 'ymir-external-id',
                        'role_name' => 'ymir-cloud-provider-124',
                    ],
                ],
            ]),
            CloudProviderFactory::create(['id' => 125, 'name' => 'Keys', 'status' => 'connected', 'authentication' => ['method' => 'access_key']]),
            CloudProviderFactory::create(['id' => 126, 'name' => 'Role', 'status' => 'connected', 'authentication' => ['method' => 'assume_role']]),
            CloudProviderFactory::create(['id' => 127, 'name' => 'Disconnected', 'status' => 'disconnected', 'authentication' => ['method' => 'assume_role']]),
        ]);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn($providers);
        $this->apiClient->shouldNotReceive('getProvider');

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListProvidersCommand::NAME);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('The following cloud provider connections belong to your team:', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/Id\s+Name\s+Status\s+Authentication/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/123\s+NoMethod\s+connected\s+Unavailable/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/124\s+Pending\s+pending\s+Unavailable/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/125\s+Keys\s+connected\s+access_key/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/126\s+Role\s+connected\s+assume_role/', $tester->getDisplay());
        $this->assertMatchesRegularExpression('/127\s+Disconnected\s+disconnected\s+assume_role/', $tester->getDisplay());
        $this->assertStringNotContainsString('012345678901', $tester->getDisplay());
        $this->assertStringNotContainsString('ymir-external-id', $tester->getDisplay());
        $this->assertStringNotContainsString('ymir-cloud-provider-124', $tester->getDisplay());
    }
}
