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
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection());

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListProvidersCommand::NAME);

        $this->assertStringContainsString('The following cloud providers are connected to your team:', $tester->getDisplay());
    }

    public function testListProvidersFailsIfNoTeamIsActive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You do not have a currently active team, but you can select a team using the "team:select" command');

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $this->executeCommand(ListProvidersCommand::NAME);
    }

    public function testListProvidersSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new ListProvidersCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(ListProvidersCommand::NAME);

        $this->assertStringContainsString('The following cloud providers are connected to your team:', $tester->getDisplay());
        $this->assertStringContainsString('123', $tester->getDisplay());
        $this->assertStringContainsString('AWS', $tester->getDisplay());
    }
}
