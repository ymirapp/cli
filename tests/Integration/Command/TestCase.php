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

namespace Ymir\Cli\Tests\Integration\Command;

use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Application;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\Output;
use Ymir\Cli\EventDispatcher\AutowiredEventDispatcher;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\ProjectLocator;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Team\TeamLocator;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var ApiClient|Mockery\MockInterface
     */
    protected $apiClient;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var CliConfiguration
     */
    protected $cliConfiguration;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $homeDir;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * @var Mockery\MockInterface|ProjectTypeInterface
     */
    protected $projectTypeMock;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupFilesystemSandbox();

        $this->apiClient = \Mockery::mock(ApiClient::class);
        $this->cliConfiguration = new CliConfiguration($this->homeDir.'/.ymir/config.json', $this->filesystem);
        $this->projectConfiguration = new ProjectConfiguration($this->filesystem, [], $this->tempDir.'/ymir.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);

        parent::tearDown();
    }

    protected function bootApplication(array $commands, array $subscribers = []): void
    {
        $this->application = new Application($commands, '1.0.0');
        $this->application->setDispatcher(new AutowiredEventDispatcher($subscribers));
        $this->application->setAutoExit(false);
    }

    protected function createExecutionContextFactory(array $resourceDefinitions = []): ExecutionContextFactory
    {
        return new ExecutionContextFactory(
            $this->apiClient,
            $this->homeDir,
            new ProjectLocator($this->apiClient, $this->projectConfiguration),
            $this->projectConfiguration,
            $this->tempDir,
            new ResourceProvisioner(),
            new ServiceLocator($resourceDefinitions),
            new TeamLocator($this->apiClient, $this->cliConfiguration)
        );
    }

    protected function createOutputMock(array $inputData = [])
    {
        $input = new ArrayInput($inputData);
        $bufferedOutput = new BufferedOutput();

        $realOutput = new Output($input, $bufferedOutput);

        return \Mockery::mock($realOutput)->makePartial();
    }

    protected function executeCommand(string $commandName, array $args = [], array $inputs = []): CommandTester
    {
        $tester = new CommandTester($this->application->find($commandName));
        $tester->setInputs($inputs);
        $tester->execute($args);

        return $tester;
    }

    /**
     * Setup an active team in the CLI configuration.
     */
    protected function setupActiveTeam(int $teamId = 1, string $teamName = 'team'): Team
    {
        $team = TeamFactory::create(['id' => $teamId, 'name' => $teamName]);

        $this->cliConfiguration->setActiveTeamId($teamId);
        $this->apiClient->shouldReceive('getTeam')->with($teamId)->andReturn($team);

        return $team;
    }

    /**
     * Setup the isolated filesystem sandbox for the test.
     */
    protected function setupFilesystemSandbox(): void
    {
        $this->tempDir = sys_get_temp_dir().'/ymir-cli-test-'.uniqid();
        $this->homeDir = $this->tempDir.'/home';
        $this->filesystem = new Filesystem();

        $this->filesystem->mkdir([
            $this->tempDir,
            $this->homeDir,
            $this->homeDir.'/.ssh',
            $this->homeDir.'/.ymir',
        ]);
    }

    /**
     * Setup a valid project state in the sandbox.
     */
    protected function setupValidProject(int $projectId = 1, string $projectName = 'project', array $environments = [], string $projectTypeSlug = 'wordpress', string $projectTypeClass = ProjectTypeInterface::class): Project
    {
        $project = ProjectFactory::create(['id' => $projectId, 'name' => $projectName]);

        $this->apiClient->shouldReceive('getProject')->with($projectId)->andReturn($project);

        $this->projectTypeMock = \Mockery::mock($projectTypeClass);
        $this->projectTypeMock->shouldReceive('getSlug')->andReturn($projectTypeSlug);

        $this->projectConfiguration = new ProjectConfiguration($this->filesystem, [$this->projectTypeMock], $this->tempDir.'/ymir.yml');
        $this->projectConfiguration->createNew($project, collect(), $this->projectTypeMock);

        foreach ($environments as $name => $config) {
            $this->projectConfiguration->addEnvironment(new EnvironmentConfiguration($name, array_merge(['console_timeout' => 60], $config)));
        }

        return $project;
    }
}
