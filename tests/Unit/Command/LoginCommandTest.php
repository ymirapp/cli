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

namespace Ymir\Cli\Tests\Unit\Command;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\LoginCommand;
use Ymir\Cli\Exception\ApiClientException;
use Ymir\Cli\Tests\Mock\ApiClientMockTrait;
use Ymir\Cli\Tests\Mock\CliConfigurationMockTrait;
use Ymir\Cli\Tests\Mock\ProjectConfigurationMockTrait;
use Ymir\Cli\Tests\Mock\PsrRequestInterfaceMockTrait;
use Ymir\Cli\Tests\Mock\PsrResponseInterfaceMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Command\LoginCommand
 */
class LoginCommandTest extends TestCase
{
    use ApiClientMockTrait;
    use CliConfigurationMockTrait;
    use ProjectConfigurationMockTrait;
    use PsrRequestInterfaceMockTrait;
    use PsrResponseInterfaceMockTrait;

    public function testLoginCancelsIfAlreadyAuthenticatedAndAnswersNoToPrompt()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);

        $commandTester = new CommandTester(new LoginCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            'no',
        ]);

        $commandTester->execute([], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('You are already logged in. Do you want to log in again?', $commandTester->getDisplay());
    }

    public function testLoginsAgainIfAlreadyAuthenticatedAndAnswersYesToPrompt()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $email = $this->faker->email;
        $password = $this->faker->password;

        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(true);

        $apiClient->expects($this->once())
                  ->method('getAccessToken')
                  ->with($this->identicalTo($email), $this->identicalTo($password))
                  ->willReturn('access_token');

        $apiClient->expects($this->once())
                  ->method('getActiveTeam')
                  ->willReturn(new Collection(['id' => 42]));

        $cliConfiguration->expects($this->once())
                         ->method('setAccessToken')
                         ->with($this->identicalTo('access_token'));

        $cliConfiguration->expects($this->once())
                         ->method('setActiveTeamId')
                         ->with($this->identicalTo(42));

        $commandTester = new CommandTester(new LoginCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            'yes',
            $email,
            $password,
        ]);

        $commandTester->execute([], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('You are already logged in. Do you want to log in again?', $output);
        $this->assertStringNotContainsString('Authentication code:', $output);
        $this->assertStringContainsString('Logged in successfully', $output);
    }

    public function testLoginWith2fa()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();
        $response = $this->getResponseInterfaceMock();

        $email = $this->faker->email;
        $password = $this->faker->password;

        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(false);

        $apiClient->expects($this->exactly(2))
                  ->method('getAccessToken')
                  ->withConsecutive(
                      [$this->identicalTo($email), $this->identicalTo($password)],
                      [$this->identicalTo($email), $this->identicalTo($password), $this->identicalTo('authentication_code')]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->throwException(new ApiClientException(new ClientException('', $this->getRequestInterfaceMock(), $response))),
                      'access_token'
                  );

        $apiClient->expects($this->once())
                  ->method('getActiveTeam')
                  ->willReturn(new Collection(['id' => 42]));

        $cliConfiguration->expects($this->once())
                         ->method('setAccessToken')
                         ->with($this->identicalTo('access_token'));

        $cliConfiguration->expects($this->once())
                         ->method('setActiveTeamId')
                         ->with($this->identicalTo(42));

        $response->expects($this->once())
                 ->method('getBody')
                 ->willReturn(json_encode(['errors' => ['authentication_code' => 'The authentication_code field is required.']]));

        $commandTester = new CommandTester(new LoginCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            $email,
            $password,
            'authentication_code',
        ]);

        $commandTester->execute([], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Authentication code:', $output);
        $this->assertStringContainsString('Logged in successfully', $output);
    }

    public function testLoginWithout2fa()
    {
        $apiClient = $this->getApiClientMock();
        $cliConfiguration = $this->getCliConfigurationMock();
        $projectConfiguration = $this->getProjectConfigurationMock();

        $email = $this->faker->email;
        $password = $this->faker->password;

        $apiClient->expects($this->once())
                  ->method('isAuthenticated')
                  ->willReturn(false);

        $apiClient->expects($this->once())
                  ->method('getAccessToken')
                  ->with($this->identicalTo($email), $this->identicalTo($password))
                  ->willReturn('access_token');

        $apiClient->expects($this->once())
                  ->method('getActiveTeam')
                  ->willReturn(new Collection(['id' => 42]));

        $cliConfiguration->expects($this->once())
                         ->method('setAccessToken')
                         ->with($this->identicalTo('access_token'));

        $cliConfiguration->expects($this->once())
                         ->method('setActiveTeamId')
                         ->with($this->identicalTo(42));

        $commandTester = new CommandTester(new LoginCommand($apiClient, $cliConfiguration, $projectConfiguration));

        $commandTester->setInputs([
            $email,
            $password,
        ]);

        $commandTester->execute([], [
            'interactive' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        $this->assertStringNotContainsString('Authentication code:', $output);
        $this->assertStringContainsString('Logged in successfully', $output);
    }
}
