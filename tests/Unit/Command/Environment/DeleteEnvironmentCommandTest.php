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

namespace Ymir\Cli\Tests\Unit\Command\Environment;

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
        $environment = $this->faker->word;
        $inputs = ['yes', 'no'];
        $input = ['environment' => $environment];
        $interactive = true;
        $expectedWithResourcesValue = false;

        $this->assertDeletesEnvironment($environment, $inputs, $input, $interactive, $expectedWithResourcesValue);
    }

    public function testDeletesEnvironmentWithoutInteraction()
    {
        $environment = $this->faker->word;
        $inputs = [];
        $input = ['environment' => $environment];
        $interactive = false;
        $expectedWithResourcesValue = false;

        $this->assertDeletesEnvironment($environment, $inputs, $input, $interactive, $expectedWithResourcesValue);
    }

    public function testDeletesEnvironmentWithoutInteractionWithResources()
    {
        $environment = $this->faker->word;
        $inputs = [];
        $input = ['environment' => $environment, '--delete-resources' => null];
        $interactive = false;
        $expectedWithResourcesValue = true;

        $this->assertDeletesEnvironment($environment, $inputs, $input, $interactive, $expectedWithResourcesValue);
    }

    public function testDeletesEnvironmentWithResources()
    {
        $environment = $this->faker->word;
        $inputs = ['yes', 'yes'];
        $input = ['environment' => $environment];
        $interactive = true;
        $expectedWithResourcesValue = true;

        $this->assertDeletesEnvironment($environment, $inputs, $input, $interactive, $expectedWithResourcesValue);
    }

    private function assertDeletesEnvironment(string $environment, array $inputs, array $input, bool $interactive, bool $expectedWithResourcesValue)
    {
        $apiClient = $this->getApiClientMock();
        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $apiClient->expects($this->once())
                  ->method('getEnvironments')
                  ->with($this->equalTo($projectConfiguration->getProjectId()))
                  ->willReturn(collect([['name' => $environment]]));

        $apiClient->expects($this->once())
                  ->method('deleteEnvironment')
                  ->with($this->equalTo($projectConfiguration->getProjectId()), $this->equalTo($environment), $this->equalTo($expectedWithResourcesValue));

        $projectConfiguration->expects($this->once())
                             ->method('deleteEnvironment')
                             ->with($this->equalTo($environment));

        $commandTester = new CommandTester(new DeleteEnvironmentCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs($inputs);

        $commandTester->execute($input, ['interactive' => $interactive]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Environment deleted', $commandTester->getDisplay());
    }
}
