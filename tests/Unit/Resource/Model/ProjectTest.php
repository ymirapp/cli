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

namespace Ymir\Cli\Tests\Unit\Resource\Model;

use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class ProjectTest extends TestCase
{
    public function testFromArraySetsId(): void
    {
        $project = Project::fromArray($this->getProjectData());

        $this->assertSame(1, $project->getId());
    }

    public function testFromArraySetsName(): void
    {
        $project = Project::fromArray($this->getProjectData());

        $this->assertSame('name', $project->getName());
    }

    public function testFromArraySetsProvider(): void
    {
        $project = Project::fromArray($this->getProjectData());

        $this->assertSame(2, $project->getProvider()->getId());
    }

    public function testFromArraySetsRegion(): void
    {
        $project = Project::fromArray($this->getProjectData());

        $this->assertSame('region', $project->getRegion());
    }

    public function testFromArraySetsRepositoryUri(): void
    {
        $project = Project::fromArray($this->getProjectData());

        $this->assertSame('uri', $project->getRepositoryUri());
    }

    public function testGetId(): void
    {
        $project = $this->createProjectModel();

        $this->assertSame(1, $project->getId());
    }

    public function testGetName(): void
    {
        $project = $this->createProjectModel();

        $this->assertSame('name', $project->getName());
    }

    public function testGetProvider(): void
    {
        $project = $this->createProjectModel();

        $this->assertInstanceOf(CloudProvider::class, $project->getProvider());
    }

    public function testGetRegion(): void
    {
        $project = $this->createProjectModel();

        $this->assertSame('region', $project->getRegion());
    }

    public function testGetRepositoryUri(): void
    {
        $project = $this->createProjectModel();

        $this->assertSame('uri', $project->getRepositoryUri());
    }

    private function createProjectModel(): Project
    {
        $user = new User(4, 'owner');
        $team = new Team(3, 'team', $user);
        $provider = new CloudProvider(2, 'provider', $team);

        return new Project(1, 'name', 'region', $provider, 'uri');
    }

    private function getProjectData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'provider' => [
                'id' => 2,
                'name' => 'provider',
                'team' => [
                    'id' => 3,
                    'name' => 'team',
                    'owner' => [
                        'id' => 4,
                        'name' => 'owner',
                    ],
                ],
            ],
            'repository_uri' => 'uri',
        ];
    }
}
