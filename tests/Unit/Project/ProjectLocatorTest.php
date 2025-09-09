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

namespace Ymir\Cli\Tests\Unit\Project;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\ProjectLocator;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class ProjectLocatorTest extends TestCase
{
    public function testGetProjectReturnsCachedProject(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $project = ProjectFactory::create();
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $projectConfiguration->shouldReceive('exists')
                             ->once()
                             ->andReturn(true);

        $projectConfiguration->shouldReceive('getProjectId')
                             ->once()
                             ->andReturn(1);

        $apiClient->shouldReceive('getProject')
                  ->once()
                  ->with(1)
                  ->andReturn($project);

        $projectLocator = new ProjectLocator($apiClient, $projectConfiguration);

        $this->assertSame($project, $projectLocator->getProject());
        $this->assertSame($project, $projectLocator->getProject());
    }

    public function testGetProjectReturnsNullIfConfigurationDoesNotExist(): void
    {
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $projectConfiguration->shouldReceive('exists')
                             ->once()
                             ->andReturn(false);

        $this->assertNull((new ProjectLocator(\Mockery::mock(ApiClient::class), $projectConfiguration))->getProject());
    }

    public function testGetProjectReturnsProjectFromApiClientIfConfigurationExists(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $project = ProjectFactory::create();
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);

        $projectConfiguration->shouldReceive('exists')
                             ->once()
                             ->andReturn(true);

        $projectConfiguration->shouldReceive('getProjectId')
                             ->once()
                             ->andReturn(1);

        $apiClient->shouldReceive('getProject')
                  ->once()
                  ->with(1)
                  ->andReturn($project);

        $this->assertSame($project, (new ProjectLocator($apiClient, $projectConfiguration))->getProject());
    }
}
