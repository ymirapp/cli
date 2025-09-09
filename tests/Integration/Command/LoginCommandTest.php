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

use Ymir\Cli\Command\LoginCommand;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Sdk\Exception\ClientException;

class LoginCommandTest extends TestCase
{
    public function testLoginSkipsIfAlreadyAuthenticatedAndUserDeclines(): void
    {
        $this->apiClient->shouldReceive('isAuthenticated')->andReturn(true);

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new LoginCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(LoginCommand::NAME, [], ['no']);

        $this->assertStringNotContainsString('Email', $tester->getDisplay());
    }

    public function testLoginSuccessfully(): void
    {
        $this->apiClient->shouldReceive('isAuthenticated')->andReturn(false);
        $this->apiClient->shouldReceive('getAccessToken')->with('test@example.com', 'password')->andReturn('token');
        $this->apiClient->shouldReceive('setAccessToken')->with('token');

        $team = TeamFactory::create(['id' => 1]);
        $this->apiClient->shouldReceive('getActiveTeam')->andReturn($team);

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new LoginCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(LoginCommand::NAME, [], ['test@example.com', 'password']);

        $this->assertStringContainsString('Logged in successfully', $tester->getDisplay());
        $this->assertEquals('token', $this->cliConfiguration->getAccessToken());
        $this->assertEquals(1, $this->cliConfiguration->getActiveTeamId());
    }

    public function testLoginWithTwoFactorAuthentication(): void
    {
        $this->apiClient->shouldReceive('isAuthenticated')->andReturn(false);

        $exception = \Mockery::mock(ClientException::class);
        $exception->shouldReceive('getValidationErrors')->andReturn(collect(['authentication_code' => ['The authentication code is required.']]));

        $this->apiClient->shouldReceive('getAccessToken')->with('test@example.com', 'password')->andThrow($exception);
        $this->apiClient->shouldReceive('getAccessToken')->with('test@example.com', 'password', '123456')->andReturn('token');
        $this->apiClient->shouldReceive('setAccessToken')->with('token');

        $team = TeamFactory::create(['id' => 1]);
        $this->apiClient->shouldReceive('getActiveTeam')->andReturn($team);

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new LoginCommand($this->apiClient, $this->cliConfiguration, $contextFactory)]);
        $tester = $this->executeCommand(LoginCommand::NAME, [], ['test@example.com', 'password', '123456']);

        $this->assertStringContainsString('Logged in successfully', $tester->getDisplay());
        $this->assertEquals('token', $this->cliConfiguration->getAccessToken());
        $this->assertEquals(1, $this->cliConfiguration->getActiveTeamId());
    }
}
