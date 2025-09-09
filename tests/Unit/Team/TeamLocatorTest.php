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

namespace Ymir\Cli\Tests\Unit\Team;

use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Team\TeamLocator;
use Ymir\Cli\Tests\TestCase;

class TeamLocatorTest extends TestCase
{
    public function testGetTeamMemoizesTeam(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $cliConfiguration = \Mockery::mock(CliConfiguration::class);
        $team = Team::fromArray(['id' => 1, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]);

        $cliConfiguration->shouldReceive('getActiveTeamId')->twice()
                         ->andReturn(1);

        $apiClient->shouldReceive('getTeam')->once()
                  ->with(1)
                  ->andReturn($team);

        $teamLocator = new TeamLocator($apiClient, $cliConfiguration);

        $this->assertSame($team, $teamLocator->getTeam());
        $this->assertSame($team, $teamLocator->getTeam());
    }

    public function testGetTeamReturnsNullIfNoActiveTeam(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $cliConfiguration = \Mockery::mock(CliConfiguration::class);

        $cliConfiguration->shouldReceive('getActiveTeamId')->once()
                         ->andReturn(null);

        $teamLocator = new TeamLocator($apiClient, $cliConfiguration);

        $this->assertNull($teamLocator->getTeam());
    }

    public function testGetTeamReturnsTeamFromApiClient(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $cliConfiguration = \Mockery::mock(CliConfiguration::class);
        $team = Team::fromArray(['id' => 1, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'foo@bar.com']]);

        $cliConfiguration->shouldReceive('getActiveTeamId')->once()
                         ->andReturn(1);

        $apiClient->shouldReceive('getTeam')->once()
                  ->with(1)
                  ->andReturn($team);

        $teamLocator = new TeamLocator($apiClient, $cliConfiguration);

        $this->assertSame($team, $teamLocator->getTeam());
    }
}
