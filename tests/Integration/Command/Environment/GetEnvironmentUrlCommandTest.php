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

namespace Ymir\Cli\Tests\Integration\Command\Environment;

use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetEnvironmentUrlCommandTest extends TestCase
{
    public function testGetEnvironmentUrl(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));

        $this->bootApplication([new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentUrlCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('https://staging.ymirapp.com', $tester->getDisplay());
    }

    public function testGetEnvironmentUrlInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject();
        $environment = EnvironmentFactory::create(['name' => 'staging', 'vanity_domain_name' => 'staging.ymirapp.com']);

        $this->apiClient->shouldReceive('getEnvironments')->with($project)->andReturn(new ResourceCollection(['staging' => $environment]));

        $this->bootApplication([new GetEnvironmentUrlCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(GetEnvironmentUrlCommand::NAME, [], ['staging']);

        $this->assertStringContainsString('https://staging.ymirapp.com', $tester->getDisplay());
    }
}
