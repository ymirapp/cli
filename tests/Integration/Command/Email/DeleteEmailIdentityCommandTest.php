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

namespace Ymir\Cli\Tests\Integration\Command\Email;

use Ymir\Cli\Command\Email\DeleteEmailIdentityCommand;
use Ymir\Cli\Resource\Definition\EmailIdentityDefinition;
use Ymir\Cli\Resource\Model\EmailIdentity;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EmailIdentityFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteEmailIdentityCommandTest extends TestCase
{
    public function testDeleteEmailIdentityCancelled(): void
    {
        $team = $this->setupActiveTeam();

        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity]));
        $this->apiClient->shouldNotReceive('deleteEmailIdentity');

        $this->bootApplication([new DeleteEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            EmailIdentity::class => function () {
                return new EmailIdentityDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(DeleteEmailIdentityCommand::NAME, ['identity' => '1'], ['n']);

        $display = $tester->getDisplay();

        $this->assertStringNotContainsString('Email identity deleted', $display);
    }

    public function testDeleteEmailIdentityWithId(): void
    {
        $team = $this->setupActiveTeam();

        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity]));
        $this->apiClient->shouldReceive('deleteEmailIdentity')->with($identity)->once();

        $this->bootApplication([new DeleteEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            EmailIdentity::class => function () {
                return new EmailIdentityDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(DeleteEmailIdentityCommand::NAME, ['identity' => '1'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Email identity deleted', $display);
    }

    public function testDeleteEmailIdentityWithName(): void
    {
        $team = $this->setupActiveTeam();

        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity]));
        $this->apiClient->shouldReceive('deleteEmailIdentity')->with($identity)->once();

        $this->bootApplication([new DeleteEmailIdentityCommand($this->apiClient, $this->createExecutionContextFactory([
            EmailIdentity::class => function () {
                return new EmailIdentityDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(DeleteEmailIdentityCommand::NAME, ['identity' => 'example.com'], ['y']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Email identity deleted', $display);
    }
}
