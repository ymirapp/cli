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

use Ymir\Cli\Command\Provider\UpdateProviderCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class UpdateProviderCommandTest extends TestCase
{
    public function testUpdateProviderSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'new-key', 'secret' => 'new-secret'], 'Updated AWS');

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], ['Updated AWS', 'new-key', 'new-secret']);

        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }

    public function testUpdateProviderWithAwsProfile(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create([
            'id' => 123,
            'name' => 'AWS',
        ]);

        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('updateProvider')->once()->with($provider, ['key' => 'profile-key', 'secret' => 'profile-secret'], 'AWS');

        $awsDir = $this->homeDir.'/.aws';
        if (!is_dir($awsDir)) {
            mkdir($awsDir);
        }

        file_put_contents($awsDir.'/credentials', "[work]\naws_access_key_id=profile-key\naws_secret_access_key=profile-secret\n");

        $contextFactory = $this->createExecutionContextFactory([
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new UpdateProviderCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(UpdateProviderCommand::NAME, ['provider' => '123'], ['AWS', 'work']);

        $this->assertStringContainsString('Available AWS credential profiles:', $tester->getDisplay());
        $this->assertStringContainsString('Cloud provider updated', $tester->getDisplay());
    }
}
