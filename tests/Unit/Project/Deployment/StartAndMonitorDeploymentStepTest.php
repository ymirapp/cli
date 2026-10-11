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

namespace Ymir\Cli\Tests\Unit\Project\Deployment;

use Symfony\Component\Process\Process;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\Project\DeploymentCancelledException;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class StartAndMonitorDeploymentStepTest extends TestCase
{
    private const SIGINT_PROCESS_CODE = <<<'PHP'
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Application;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\StartAndMonitorDeploymentStep;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;

require $argv[1];

$status = $argv[2];
$apiClient = Mockery::mock(ApiClient::class);
$context = Mockery::mock(ExecutionContext::class);
$deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
$exception = null;

$context->shouldReceive('getProject')->andReturn(ProjectFactory::create());
$context->shouldReceive('getApiClient')->andReturn($apiClient);

$apiClient->shouldReceive('startDeployment')->once()->with($deployment)->andReturnUsing(function (): void {
    posix_kill(getmypid(), SIGINT);
});

if ('rejected' === $status) {
    $apiClient->shouldReceive('cancelDeployment')->once()->with($deployment)->andThrow(new RuntimeException('Cancellation rejected'));
    $apiClient->shouldNotReceive('getDeployment');
} else {
    $apiClient->shouldReceive('cancelDeployment')->once()->with($deployment);
    $apiClient->shouldReceive('getDeployment')->times('cancelled' === $status ? 1 : 2)->with(1)->andReturn(DeploymentFactory::create([
        'id' => 1,
        'type' => 'deployment',
        'status' => $status,
        'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']],
    ]));
}

$application = new Application([], 'test');
$application->setAutoExit(false);
$application->register('deploy')->setCode(function (InputInterface $input, OutputInterface $output) use ($context, $deployment, &$exception): int {
    $context->shouldReceive('getOutput')->andReturn(new Output($input, $output));

    try {
        (new StartAndMonitorDeploymentStep())->perform($context, $deployment, EnvironmentFactory::create());
    } catch (Throwable $throwable) {
        $exception = $throwable;

        throw $throwable;
    }

    $output->writeln('Deployment completed');

    return 0;
});

$output = new BufferedOutput();
$exitCode = $application->run(new ArrayInput(['command' => 'deploy', '--verbose' => true]), $output);

Mockery::close();

echo json_encode([
    'exit_code' => $exitCode,
    'display' => $output->fetch(),
    'exception' => $exception instanceof Throwable ? get_class($exception) : null,
    'message' => $exception instanceof Throwable ? $exception->getMessage() : null,
]);

exit($exitCode);
PHP;

    public function testPerformCompletesWhenInterruptedDeploymentFinishes(): void
    {
        $result = $this->runInterruptedDeployment('finished');

        $this->assertSame(0, $result['exit_code']);
        $this->assertNull($result['exception']);
        $this->assertStringContainsString('Attempting to cancel the deployment', $result['display']);
        $this->assertStringContainsString('Creating infrastructure', $result['display']);
        $this->assertStringContainsString('Deployment completed', $result['display']);
        $this->assertStringNotContainsString('Deployment cancelled', $result['display']);
    }

    public function testPerformOnlyPrintsStartedDeploymentStepsOnceAcrossPolls(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $printedSteps = [];
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $poll = 0;
        $polls = [
            DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [
                ['id' => 1, 'task' => 'PrepareDeployment', 'status' => 'finished'],
                ['id' => 2, 'task' => 'CreateInfrastructure', 'status' => 'running'],
                ['id' => 3, 'task' => 'ConfigureEnvironment', 'status' => 'pending'],
                ['id' => 4, 'task' => 'WarmApplication', 'status' => 'pending'],
            ]]),
            DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [
                ['id' => 1, 'task' => 'PrepareDeployment', 'status' => 'finished'],
                ['id' => 2, 'task' => 'CreateInfrastructure', 'status' => 'finished'],
                ['id' => 3, 'task' => 'ConfigureEnvironment', 'status' => 'running'],
                ['id' => 4, 'task' => 'WarmApplication', 'status' => 'pending'],
            ]]),
            DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'steps' => [
                ['id' => 1, 'task' => 'PrepareDeployment', 'status' => 'finished'],
                ['id' => 2, 'task' => 'CreateInfrastructure', 'status' => 'finished'],
                ['id' => 3, 'task' => 'ConfigureEnvironment', 'status' => 'finished'],
                ['id' => 4, 'task' => 'WarmApplication', 'status' => 'finished'],
            ]]),
        ];

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->times(3)
                  ->with(1)
                  ->andReturnUsing(function () use (&$poll, $polls, &$printedSteps): Deployment {
                      if (1 === $poll) {
                          $this->assertSame(['Preparing deployment', 'Creating infrastructure'], $printedSteps);
                      } elseif (2 === $poll) {
                          $this->assertSame(['Preparing deployment', 'Creating infrastructure', 'Configuring environment'], $printedSteps);
                      }

                      return $polls[$poll++];
                  });

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldReceive('writeStep')->times(4)
               ->andReturnUsing(function (string $stepName) use (&$printedSteps): void {
                   $printedSteps[] = $stepName;
               });

        $step = new StartAndMonitorDeploymentStep();

        $step->perform($context, $deployment, $environment);

        $this->assertSame(['Preparing deployment', 'Creating infrastructure', 'Configuring environment', 'Warming application'], $printedSteps);
    }

    public function testPerformStartsAndMonitorsDeployment(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(
                      DeploymentFactory::create(['id' => 1, 'status' => 'running', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'finished', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'finished']]])
                  );

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldReceive('writeStep')->once()
               ->with('Creating infrastructure');

        $step = new StartAndMonitorDeploymentStep();

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWhenCancelledDeploymentHasFailedSteps(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient->shouldReceive('getDeployment')->once()
                  ->with(1)
                  ->andReturn(DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'cancelled', 'failed_message' => 'Terraform crashed', 'steps' => [
                      ['id' => 1, 'task' => 'PrepareDeployment', 'status' => 'finished'],
                      ['id' => 2, 'task' => 'CreateInfrastructure', 'status' => 'failed'],
                  ]]));

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldNotReceive('writeStep');

        $this->expectException(DeploymentCancelledException::class);
        $this->expectExceptionMessage('Deployment cancelled');

        (new StartAndMonitorDeploymentStep())->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWhenDeploymentFails(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'deployment', 'status' => 'pending']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient
                  ->shouldReceive('getDeployment')
                  ->with(1)
                  ->andReturn(
                      DeploymentFactory::create(['id' => 1, 'status' => 'failed', 'steps' => [['id' => 1, 'task' => 'CreateInfrastructure', 'status' => 'failed']]]),
                      DeploymentFactory::create(['id' => 1, 'status' => 'failed', 'failed_message' => 'Terraform crashed'])
                  );

        $output->shouldReceive('info')->once()
               ->with(sprintf('Deploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $output->shouldNotReceive('writeStep');

        $step = new StartAndMonitorDeploymentStep();

        $this->expectException(DeploymentFailedException::class);
        $this->expectExceptionMessage("Deployment failed with error message:\n\n\tTerraform crashed");

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWhenDeploymentIsCancelled(): void
    {
        $types = [
            'deployment' => ['Deploying', 'Deployment cancelled'],
            'redeployment' => ['Redeploying', 'Redeployment cancelled'],
            'rollback' => ['Rolling back', 'Rollback cancelled'],
        ];

        foreach ($types as $type => [$verb, $message]) {
            $apiClient = \Mockery::mock(ApiClient::class);
            $context = \Mockery::mock(ExecutionContext::class);
            $deployment = DeploymentFactory::create(['id' => 1, 'type' => $type, 'status' => 'pending']);
            $environment = EnvironmentFactory::create();
            $output = \Mockery::mock(Output::class);
            $project = ProjectFactory::create();

            $context->shouldReceive('getProject')->once()
                    ->andReturn($project);

            $context->shouldReceive('getApiClient')->once()
                    ->andReturn($apiClient);

            $context->shouldReceive('getOutput')->once()
                    ->andReturn($output);

            $apiClient->shouldReceive('startDeployment')->once()
                      ->with($deployment);

            $apiClient->shouldReceive('getDeployment')->once()
                      ->with(1)
                      ->andReturn(DeploymentFactory::create(['id' => 1, 'type' => $type, 'status' => 'cancelled']));

            $apiClient->shouldNotReceive('cancelDeployment');

            $output->shouldReceive('info')->once()
                   ->with(sprintf('%s <comment>%s</comment> to <comment>%s</comment>', $verb, $project->getName(), $environment->getName()));

            $output->shouldNotReceive('writeStep');

            try {
                (new StartAndMonitorDeploymentStep())->perform($context, $deployment, $environment);
                $this->fail(sprintf('Expected the cancelled %s to throw an exception', $type));
            } catch (DeploymentCancelledException $exception) {
                $this->assertNotInstanceOf(DeploymentFailedException::class, $exception);
                $this->assertSame($message, $exception->getMessage());
                $this->assertSame(130, $exception->getCode());
            }
        }
    }

    public function testPerformThrowsExceptionWhenInterruptedDeploymentCancellationIsRejected(): void
    {
        $result = $this->runInterruptedDeployment('rejected');

        $this->assertSame(1, $result['exit_code']);
        $this->assertSame(RuntimeException::class, $result['exception']);
        $this->assertSame('Cancellation rejected', $result['message']);
        $this->assertStringContainsString('[Ymir\Cli\Exception\RuntimeException]', $result['display']);
        $this->assertStringContainsString('Attempting to cancel the deployment', $result['display']);
        $this->assertStringNotContainsString('Deployment cancelled', $result['display']);
        $this->assertStringNotContainsString('Deployment completed', $result['display']);
    }

    public function testPerformThrowsExceptionWhenInterruptedDeploymentIsCancelled(): void
    {
        $result = $this->runInterruptedDeployment('cancelled');

        $this->assertSame(130, $result['exit_code']);
        $this->assertSame(DeploymentCancelledException::class, $result['exception']);
        $this->assertSame('Deployment cancelled', $result['message']);
        $this->assertStringEndsWith('Attempting to cancel the deployment'.PHP_EOL.'Deployment cancelled'.PHP_EOL, $result['display']);
        $this->assertStringNotContainsString('Exception trace', $result['display']);
        $this->assertStringNotContainsString('Deployment completed', $result['display']);
    }

    public function testPerformThrowsExceptionWhenStartedDeploymentIsAlreadyCancelled(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['id' => 1, 'type' => 'redeployment', 'status' => 'cancelled']);
        $environment = EnvironmentFactory::create();
        $output = \Mockery::mock(Output::class);
        $project = ProjectFactory::create();

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('startDeployment')->once()
                  ->with($deployment);

        $apiClient->shouldNotReceive('getDeployment');

        $output->shouldReceive('info')->once()
               ->with(sprintf('Redeploying <comment>%s</comment> to <comment>%s</comment>', $project->getName(), $environment->getName()));

        $this->expectException(DeploymentCancelledException::class);
        $this->expectExceptionMessage('Redeployment cancelled');

        (new StartAndMonitorDeploymentStep())->perform($context, $deployment, $environment);
    }

    private function runInterruptedDeployment(string $status): array
    {
        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('Sending a real SIGINT to the deployment monitor requires the "pcntl" and "posix" extensions');
        }

        $process = new Process([PHP_BINARY, '-d', 'display_errors=stderr', '-r', self::SIGINT_PROCESS_CODE, dirname(__DIR__, 4).'/vendor/autoload.php', $status]);
        $process->setTimeout(30);
        $process->run();

        $result = json_decode($process->getOutput(), true);

        $this->assertIsArray($result, $process->getOutput().$process->getErrorOutput());
        $this->assertSame($result['exit_code'], $process->getExitCode());

        return $result;
    }
}
