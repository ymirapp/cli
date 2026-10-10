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

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Environment\EnvironmentDownCommand;
use Ymir\Cli\Exception\NonInteractiveRequiredArgumentException;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\DeploymentStepInterface;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;
use Ymir\Sdk\Exception\ClientException;

class EnvironmentDownCommandTest extends TestCase
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
        $this->apiClient->shouldNotReceive('cancelDeployment');

        $this->bootMaintenanceCommand([new StartAndMonitorDeploymentStep()]);
    }

    public function testConfigureDescribesMaintenanceMode(): void
    {
        $command = $this->application->find(EnvironmentDownCommand::NAME);

        $this->assertSame('Put an environment in maintenance mode', $command->getDescription());
        $this->assertSame('The name of the environment to put in maintenance mode', $command->getDefinition()->getArgument('environment')->getDescription());
    }

    public function testPerformDeclinesMaintenanceModeByDefault(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('This will redeploy the "staging" environment of the "my-project" project and rerun deployment commands', $tester->getDisplay());
        $this->assertStringContainsString('Are you sure you want to put it in maintenance mode?', $tester->getDisplay());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
    }

    public function testPerformDeclinesMaintenanceModeWhenConfirmationIsRefused(): void
    {
        $configuration = file_get_contents($this->tempDir.'/ymir.yml');
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['no']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
        $this->assertSame($configuration, file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertDirectoryDoesNotExist($this->tempDir.'/.ymir');
    }

    public function testPerformPromptsForEnvironmentIfNoneProvided(): void
    {
        $this->expectRedeployment(1);

        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, [], ['staging', 'yes']);

        $this->assertStringContainsString('Which my-project environment would you like to put in maintenance mode?', $tester->getDisplay());
        $this->assertStringContainsString('This will redeploy the "staging" environment of the "my-project" project and rerun deployment commands', $tester->getDisplay());
        $this->assertStringContainsString('Are you sure you want to put it in maintenance mode?', $tester->getDisplay());
        $this->assertStringContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
    }

    public function testPerformPropagatesDeploymentStepFailure(): void
    {
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);
        $firstStep = \Mockery::mock(DeploymentStepInterface::class);
        $secondStep = \Mockery::mock(DeploymentStepInterface::class);

        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andReturn($redeployment);
        $this->apiClient->shouldNotReceive('getDeployment');
        $this->apiClient->shouldNotReceive('startDeployment');
        $firstStep->shouldReceive('perform')->once()->with(\Mockery::type(ExecutionContext::class), $redeployment, $this->environment)->andThrow(new \RuntimeException('Deployment step failed'));
        $secondStep->shouldNotReceive('perform');

        $this->bootMaintenanceCommand([$firstStep, $secondStep]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deployment step failed');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformPropagatesMonitoredRedeploymentFailure(): void
    {
        $this->expectRedeployment(1, 'failed', 'Deployment command failed');

        $this->expectException(DeploymentFailedException::class);
        $this->expectExceptionMessage('Deployment failed with error message:'."\n\n\t".'Deployment command failed');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformPropagatesRedeploymentCreationError(): void
    {
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andThrow($this->createClientException('No deployment to redeploy'));
        $this->apiClient->shouldNotReceive('startDeployment');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('No deployment to redeploy');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformPropagatesRedeploymentStartError(): void
    {
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);

        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andReturn($redeployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($redeployment)->ordered()->andThrow($this->createClientException('A deployment is already running'));
        $this->apiClient->shouldNotReceive('getDeployment');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('A deployment is already running');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformRedeploysAgainForRepeatedRequest(): void
    {
        $this->expectRedeployment(1);
        $this->expectRedeployment(2);

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testPerformRejectsRedeploymentWithoutId(): void
    {
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andReturn(DeploymentFactory::create(['id' => 0, 'type' => 'redeployment']));
        $this->apiClient->shouldNotReceive('startDeployment');

        $this->expectException(DeploymentFailedException::class);
        $this->expectExceptionMessage('There was an error creating the redeployment');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformRejectsUnknownEnvironmentBeforeChangingVariables(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a environment with "missing" as the ID or name');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'missing']);
    }

    public function testPerformRequestsMaintenanceModeAndRedeploysWithoutBuildingOrChangingLocalConfiguration(): void
    {
        $configuration = file_get_contents($this->tempDir.'/ymir.yml');
        $this->expectRedeployment(1);

        $tester = new CommandTester($this->application->find(EnvironmentDownCommand::NAME));
        $tester->execute(['environment' => 'staging'], ['interactive' => false]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Redeploying my-project to staging', $tester->getDisplay());
        $this->assertStringContainsString('Updating functions', $tester->getDisplay());
        $this->assertStringNotContainsString('Are you sure you want to', $tester->getDisplay());
        $this->assertStringContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
        $this->assertSame($configuration, file_get_contents($this->tempDir.'/ymir.yml'));
        $this->assertDirectoryDoesNotExist($this->tempDir.'/.ymir');
    }

    public function testPerformRequiresEnvironmentWhenNotInteractive(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');

        $this->expectException(NonInteractiveRequiredArgumentException::class);
        $this->expectExceptionMessage('You must pass a "environment" argument when running in non-interactive mode');

        (new CommandTester($this->application->find(EnvironmentDownCommand::NAME)))->execute([], ['interactive' => false]);
    }

    public function testPerformRunsDeploymentStepsInOrder(): void
    {
        $redeployment = DeploymentFactory::create(['id' => 1, 'status' => 'pending', 'type' => 'redeployment']);
        $firstStep = \Mockery::mock(DeploymentStepInterface::class);
        $secondStep = \Mockery::mock(DeploymentStepInterface::class);

        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->globally()->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->globally()->ordered()->andReturn($redeployment);
        $firstStep->shouldReceive('perform')->once()->with(\Mockery::type(ExecutionContext::class), $redeployment, $this->environment)->globally()->ordered();
        $secondStep->shouldReceive('perform')->once()->with(\Mockery::type(ExecutionContext::class), $redeployment, $this->environment)->globally()->ordered();
        $this->apiClient->shouldNotReceive('getDeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $this->bootMaintenanceCommand([$firstStep, $secondStep]);

        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
    }

    public function testPerformStopsWhenVariableRequestFails(): void
    {
        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->andThrow($this->createClientException('Unable to change environment variables'));
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Unable to change environment variables');

        $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'staging'], ['yes']);
    }

    public function testPerformUsesResolvedEnvironmentForConfirmation(): void
    {
        $this->apiClient->shouldNotReceive('changeEnvironmentVariables');
        $this->apiClient->shouldNotReceive('createRedeployment');
        $this->apiClient->shouldNotReceive('startDeployment');

        $tester = $this->executeCommand(EnvironmentDownCommand::NAME, ['environment' => 'production'], ['no']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('This will redeploy the "production" environment of the "my-project" project and rerun deployment commands. Are you sure you want to put it in maintenance mode?', $tester->getDisplay());
        $this->assertStringNotContainsString('Environment redeployed after requesting maintenance mode', $tester->getDisplay());
    }

    private function bootMaintenanceCommand(array $deploymentSteps): void
    {
        $this->bootApplication([
            new EnvironmentDownCommand($this->apiClient, $this->createExecutionContextFactory([
                Environment::class => function () { return new EnvironmentDefinition(); },
            ]), $deploymentSteps),
        ]);
    }

    private function createClientException(string $message): ClientException
    {
        return new ClientException(new GuzzleClientException($message, new Request('post', '/projects/1/environments/staging'), new Response(409, [], (string) json_encode(['message' => $message]))));
    }

    private function expectRedeployment(int $id, string $status = 'finished', string $failedMessage = ''): void
    {
        $redeployment = DeploymentFactory::create(['id' => $id, 'status' => 'pending', 'type' => 'redeployment']);

        $this->apiClient->shouldReceive('changeEnvironmentVariables')->once()->with($this->project, $this->environment, ['YMIR_MAINTENANCE_MODE' => 'true'])->ordered();
        $this->apiClient->shouldReceive('createRedeployment')->once()->with($this->project, $this->environment)->ordered()->andReturn($redeployment);
        $this->apiClient->shouldReceive('startDeployment')->once()->with($redeployment)->ordered();
        $this->apiClient->shouldReceive('getDeployment')->with($id)->andReturn(DeploymentFactory::create([
            'id' => $id,
            'status' => $status,
            'type' => 'redeployment',
            'failed_message' => $failedMessage,
            'steps' => [
                ['id' => 1, 'task' => 'UpdateFunctionsTask', 'status' => 'failed' === $status ? 'failed' : 'finished'],
            ],
        ]));
    }
}
