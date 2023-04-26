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

namespace Command\Environment;

use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Environment\DeleteEnvironmentCommand;
use Ymir\Cli\Tests\Mock\ApiClientMockTrait;
use Ymir\Cli\Tests\Mock\CliConfigurationMockTrait;
use Ymir\Cli\Tests\Mock\ProjectConfigurationMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Command\Environment\DeleteEnvironmentCommand
 */
class DeleteEnvironmentCommandTest extends TestCase
{
    use ApiClientMockTrait;
    use CliConfigurationMockTrait;
    use ProjectConfigurationMockTrait;

    public function testDeletesEnvironment()
    {
        $apiClient = $this->getApiClientMock();
        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $environment = $this->faker->word;

        $apiClient->expects($this->once())
                  ->method('getEnvironments')
                  ->with($this->equalTo($projectConfiguration->getProjectId()))
                  ->willReturn(collect([['name' => $environment]]));

        $apiClient->expects($this->once())
                  ->method('deleteEnvironment')
                  ->with($this->equalTo($projectConfiguration->getProjectId()), $this->equalTo($environment), $this->equalTo(false));

        $projectConfiguration->expects($this->once())
                             ->method('deleteEnvironment')
                             ->with($this->equalTo($environment));

        $commandTester = new CommandTester(new DeleteEnvironmentCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            'yes',
            'no',
        ]);

        $commandTester->execute([
            'environment' => $environment,
        ], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Environment deleted', $commandTester->getDisplay());
    }

    public function testDeletesEnvironmentWithoutInteraction()
    {
        $apiClient = $this->getApiClientMock();
        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $environment = $this->faker->word;

        $apiClient->expects($this->once())
                  ->method('getEnvironments')
                  ->with($this->equalTo($projectConfiguration->getProjectId()))
                  ->willReturn(collect([['name' => $environment]]));

        $apiClient->expects($this->once())
            ->method('deleteEnvironment')
            ->with($this->equalTo($projectConfiguration->getProjectId()), $this->equalTo($environment), $this->equalTo(false));

        $projectConfiguration->expects($this->once())
                             ->method('deleteEnvironment')
                             ->with($this->equalTo($environment));

        $commandTester = new CommandTester(new DeleteEnvironmentCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->execute([
            'environment' => $environment,
            '--confirm' => null,
        ], [
            'interactive' => false,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Environment deleted', $commandTester->getDisplay());
    }

    public function testDeletesEnvironmentWithoutInteractionWithResources()
    {
        $apiClient = $this->getApiClientMock();
        $apiClient->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $environment = $this->faker->word;

        $apiClient->expects($this->once())
                  ->method('getEnvironments')
                  ->with($this->equalTo($projectConfiguration->getProjectId()))
                  ->willReturn(collect([['name' => $environment]]));

        $apiClient->expects($this->once())
                  ->method('deleteEnvironment')
                  ->with($this->equalTo($projectConfiguration->getProjectId()), $this->equalTo($environment), $this->equalTo(true));

        $projectConfiguration->expects($this->once())
                             ->method('deleteEnvironment')
                             ->with($this->equalTo($environment));

        $commandTester = new CommandTester(new DeleteEnvironmentCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->execute([
            'environment' => $environment,
            '--confirm' => null,
            '--delete-resources' => null,
        ], [
            'interactive' => false,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Environment deleted', $commandTester->getDisplay());
    }

    public function testDeletesEnvironmentWithResources()
    {
        $apiClient = $this->getApiClientMock();
        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $environment = $this->faker->word;

        $apiClient->expects($this->once())
                  ->method('getEnvironments')
                  ->with($this->equalTo($projectConfiguration->getProjectId()))
                  ->willReturn(collect([['name' => $environment]]));

        $apiClient->expects($this->once())
                  ->method('deleteEnvironment')
                  ->with($this->equalTo($projectConfiguration->getProjectId()), $this->equalTo($environment), $this->equalTo(true));

        $projectConfiguration->expects($this->once())
                             ->method('deleteEnvironment')
                             ->with($this->equalTo($environment));

        $commandTester = new CommandTester(new DeleteEnvironmentCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            'yes',
            'yes',
        ]);

        $commandTester->execute([
            'environment' => $environment,
        ], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Environment deleted', $commandTester->getDisplay());
    }
}
