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

use Ymir\Cli\Command\Provider\DeleteProviderCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteProviderCommandTest extends TestCase
{
    public function testDeleteProviderCancelled(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('deleteProvider');

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new DeleteProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteProviderCommand::NAME, ['provider' => '123'], ['no']);

        $this->assertStringNotContainsString('Cloud provider deleted', $tester->getDisplay());
    }

    public function testDeleteProviderSuccessfullyWithChoice(): void
    {
        $team = $this->setupActiveTeam();
        $provider1 = CloudProviderFactory::create([
            'id' => 1,
            'name' => 'AWS 1',
        ]);

        $provider2 = CloudProviderFactory::create([
            'id' => 2,
            'name' => 'AWS 2',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider1, $provider2]));
        $this->apiClient->shouldReceive('deleteProvider')->once()->with($provider2);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new DeleteProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteProviderCommand::NAME, [], ['2', 'yes']);

        $this->assertStringContainsString('Cloud provider deleted', $tester->getDisplay());
    }

    public function testDeleteProviderSuccessfullyWithId(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('deleteProvider')->once()->with($provider);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new DeleteProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteProviderCommand::NAME, ['provider' => '123'], ['yes']);

        $this->assertStringContainsString('Cloud provider deleted', $tester->getDisplay());
    }

    public function testDeleteProviderSuccessfullyWithName(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('deleteProvider')->once()->with($provider);

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new DeleteProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteProviderCommand::NAME, ['provider' => '123'], ['yes']);

        $this->assertStringContainsString('Cloud provider deleted', $tester->getDisplay());
    }

    public function testDeleteProviderSuccessfullyWithProjectFallback(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();
        $provider = $project->getProvider();

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('deleteProvider')->once()->with(\Mockery::on(function ($argument) use ($provider) {
            return $argument instanceof CloudProvider && $argument->getId() === $provider->getId();
        }));

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new DeleteProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteProviderCommand::NAME, [], ['yes']);

        $this->assertStringContainsString('Cloud provider deleted', $tester->getDisplay());
    }
}
