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

use Ymir\Cli\Command\Project\ValidateProjectCommand;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ValidateProjectCommandTest extends TestCase
{
    private $dockerfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerfile = \Mockery::mock(Dockerfile::class);
    }

    public function testPerformThrowsExceptionIfEnvironmentNotFound(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Environment "staging" not found in ymir.yml file');

        $this->setupValidProject(1, 'my-project', ['production' => []]);

        $this->bootApplication([new ValidateProjectCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $this->executeCommand(ValidateProjectCommand::NAME, ['environments' => ['staging']]);
    }

    public function testPerformValidatesConfiguration(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);

        $this->apiClient->shouldReceive('validateProjectConfiguration')->once()
                        ->with(\Mockery::type(Project::class), \Mockery::any(), ['production'])
                        ->andReturn(collect(['production' => ['warnings' => []]]));

        $this->bootApplication([new ValidateProjectCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(ValidateProjectCommand::NAME);

        $this->assertStringContainsString('Project ymir.yml file is valid', $tester->getDisplay());
    }

    public function testPerformValidatesConfigurationWithWarnings(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);

        $this->apiClient->shouldReceive('validateProjectConfiguration')->once()
                        ->andReturn(collect(['production' => ['warnings' => ['Something is wrong']]]));

        $this->bootApplication([new ValidateProjectCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $tester = $this->executeCommand(ValidateProjectCommand::NAME);

        $this->assertStringContainsString('Project ymir.yml file is valid with the following warnings:', $tester->getDisplay());
        $this->assertStringContainsString('Something is wrong', $tester->getDisplay());
    }

    public function testPerformValidatesDockerfileForImageDeployment(): void
    {
        $project = $this->setupValidProject(1, 'my-project', [
            'production' => [
                'deployment' => ['type' => 'image'],
                'architecture' => 'arm64',
            ],
        ]);

        $this->dockerfile->shouldReceive('validate')->once()->with('production', 'arm64');
        $this->apiClient->shouldReceive('validateProjectConfiguration')->once()->andReturn(collect(['production' => ['warnings' => []]]));

        $this->bootApplication([new ValidateProjectCommand($this->apiClient, $this->createExecutionContextFactory(), $this->dockerfile)]);

        $this->executeCommand(ValidateProjectCommand::NAME);
    }
}
