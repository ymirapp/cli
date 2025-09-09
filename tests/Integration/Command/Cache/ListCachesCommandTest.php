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

namespace Ymir\Cli\Tests\Integration\Command\Cache;

use Ymir\Cli\Command\Cache\ListCachesCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CacheClusterFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListCachesCommandTest extends TestCase
{
    public function testListCachesEmpty(): void
    {
        $team = $this->setupActiveTeam();
        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection());

        $this->bootApplication([new ListCachesCommand($this->apiClient, $this->createExecutionContextFactory())]);
        $tester = $this->executeCommand(ListCachesCommand::NAME);

        $this->assertStringNotContainsString('123', $tester->getDisplay());
    }

    public function testListCachesSuccessfully(): void
    {
        $team = $this->setupActiveTeam();
        $cache = CacheClusterFactory::create([
            'id' => 123,
            'name' => 'My Cache',
        ]);

        $this->apiClient->shouldReceive('getCaches')->with($team)->andReturn(new ResourceCollection([$cache]));

        $this->bootApplication([new ListCachesCommand($this->apiClient, $this->createExecutionContextFactory())]);
        $tester = $this->executeCommand(ListCachesCommand::NAME);

        $this->assertStringContainsString('123', $tester->getDisplay());
        $this->assertStringContainsString('My Cache', $tester->getDisplay());
    }
}
