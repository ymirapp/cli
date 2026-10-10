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

use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Environment\EnvironmentUpCommand;
use Ymir\Cli\Exception\NonInteractiveRequiredArgumentException;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class EnvironmentUpCommandTest extends TestCase
{
    private $environment;

    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupActiveTeam();
        $this->project = $this->setupValidProject(1, 'my-project', ['production' => [], 'staging' => []]);
        $this->environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with($this->project)->andReturn(new ResourceCollection([
            EnvironmentFactory::create(['name' => 'production']),
            $this->environment,
        ]));
        $this->apiClient->shouldNotReceive('createDeployment');
        $this->apiClient->shouldNotReceive('getArtifactUploadUrl');

        $this->bootApplication([
            new EnvironmentUpCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ]), [new StartAndMonitorDeploymentStep()]),
        ]);
    }

    public function testConfigureDescribesMaintenanceModeRemoval(): void
    {
        $command = $this->application->find(EnvironmentUpCommand::NAME);

        $this->assertSame('Take an environment out of maintenance mode', $command->getDescription());
        $this->assertSame('The name of the environment to take out of maintenance mode', $command->getDefinition()->getArgument('environment')->getDescription());
    }

    public function testPerformDeclinesMaintenanceModeRemovalByDefault(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentUpCommand::NAME, ['environment' => 'staging'], ['']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('This will redeploy the "staging" environment of the "my-project" project and rerun deployment commands', $tester->getDisplay());
        $this->assertStringContainsString('Are you sure you want to take it out of maintenance mode?', $tester->getDisplay());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode removal', $tester->getDisplay());
    }

    public function testPerformDeclinesMaintenanceModeRemovalWhenConfirmationIsRefused(): void
    {
        $configuration = file_get_contents($this->tempDir.'/ymir.yml');
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentUpCommand::NAME, ['environment' => 'staging'], ['no']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode removal', $tester->getDisplay());
        $this->assertSame($configuration, file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertDirectoryDoesNotExist($this->tempDir.'/.ymir');
    }

    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->expectRedeployment();

        $tester = $this->executeCommand(EnvironmentUpCommand::NAME, [], ['staging', 'yes']);

        $this->assertStringContainsString('Which my-project environment would you like to take out of maintenance mode?', $tester->getDisplay());
        $this->assertStringContainsString('This will redeploy the "staging" environment of the "my-project" project and rerun deployment commands', $tester->getDisplay());
        $this->assertStringContainsString('Are you sure you want to take it out of maintenance mode?', $tester->getDisplay());
        $this->assertStringContainsString('Environment redeployed after requesting maintenance mode removal', $tester->getDisplay());
    }

    public function testPerformRequestsMaintenanceModeRemovalAndRedeploys(): void
    {
        $configuration = file_get_contents($this->tempDir.'/ymir.yml');
        $this->expectRedeployment();

        $tester = new CommandTester($this->application->find(EnvironmentUpCommand::NAME));
        $tester->execute(['environment' => 'staging'], ['interactive' => false]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Redeploying my-project to staging', $tester->getDisplay());
        $this->assertStringNotContainsString('Are you sure you want to', $tester->getDisplay());
        $this->assertStringContainsString('Environment redeployed after requesting maintenance mode removal', $tester->getDisplay());
        $this->assertSame($configuration, file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertDirectoryDoesNotExist($this->tempDir.'/.ymir');
    }

    public function testPerformRequiresEnvironmentWhenNotInteractive(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');

        $this->expectException(NonInteractiveRequiredArgumentException::class);
        $this->expectExceptionMessage('You must pass a "environment" argument when running in non-interactive mode');

        (new CommandTester($this->application->find(EnvironmentUpCommand::NAME)))->execute([], ['interactive' => false]);
    }

    public function testPerformUsesResolvedEnvironmentForConfirmation(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentUpCommand::NAME, ['environment' => 'production'], ['no']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('This will redeploy the "production" environment of the "my-project" project and rerun deployment commands. Are you sure you want to take it out of maintenance mode?', $tester->getDisplay());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode removal', $tester->getDisplay());
    }

    private function expectRedeployment(): void
    {
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);

        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'false'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andReturn($redeployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($redeployment)->ordered();
        $this->apiClient->shouldReceive('getDeployment')->with(1)->andReturn(DeploymentFactory::create([
            'id' => 1,
            'status' => 'finished',
            'type' => 'redeployment',
            'steps' => [
                ['id' => 1, 'task' => 'UpdateFunctionsTask', 'status' => 'finished'],
            ],
        ]));
    }
}
