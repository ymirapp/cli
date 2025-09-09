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

namespace Ymir\Cli\Tests\Integration\Command\Project;

use Ymir\Cli\Command\Project\ListProjectsCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListProjectsCommandTest extends TestCase
{
    public function testPerformListsProjects(): void
    {
        $team = $this->setupActiveTeam();
        $project1 = ProjectFactory::create([
            'id' => 1,
            'name' => 'project-1',
            'provider' => [
                'id' => 1,
                'name' => 'aws',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'support@ymirapp.com'],
                ],
            ],
            'region' => 'us-east-1',
        ]);
        $project2 = ProjectFactory::create([
            'id' => 2,
            'name' => 'project-2',
            'provider' => [
                'id' => 1,
                'name' => 'aws',
                'team' => [
                    'id' => 1,
                    'name' => 'team',
                    'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'support@ymirapp.com'],
                ],
            ],
            'region' => 'us-west-2',
        ]);

        $this->apiClient->shouldReceive('getProjects')->with($team)->andReturn(new ResourceCollection([$project1, $project2]));

        $this->bootApplication([new ListProjectsCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $tester = $this->executeCommand(ListProjectsCommand::NAME);

        $this->assertStringContainsString('1', $tester->getDisplay());
        $this->assertStringContainsString('project-1', $tester->getDisplay());
        $this->assertStringContainsString('2', $tester->getDisplay());
        $this->assertStringContainsString('project-2', $tester->getDisplay());
        $this->assertStringContainsString('aws', $tester->getDisplay());
        $this->assertStringContainsString('us-east-1', $tester->getDisplay());
        $this->assertStringContainsString('us-west-2', $tester->getDisplay());
    }
}
