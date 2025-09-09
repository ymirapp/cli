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

use Ymir\Cli\Command\Email\GetEmailIdentityInfoCommand;
use Ymir\Cli\Resource\Definition\EmailIdentityDefinition;
use Ymir\Cli\Resource\Model\EmailIdentity;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EmailIdentityFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetEmailIdentityInfoCommandTest extends TestCase
{
    public function testGetEmailIdentityInfoWithId(): void
    {
        $team = $this->setupActiveTeam();

        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
            'type' => 'domain',
            'verified' => true,
            'managed' => false,
            'dkim_authentication_records' => [
                ['name' => 'selector1._domainkey.example.com', 'type' => 'cname', 'value' => 'selector1.dkim.amazonses.com'],
            ],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity]));

        $this->bootApplication([new GetEmailIdentityInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            EmailIdentity::class => function () {
                return new EmailIdentityDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(GetEmailIdentityInfoCommand::NAME, ['identity' => '1']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('example.com', $display);
        $this->assertStringContainsString('domain', $display);
        $this->assertStringContainsString('yes', $display); // verified
        $this->assertStringContainsString('no', $display);  // managed
        $this->assertStringContainsString('selector1._domainkey.example.com', $display);
        $this->assertStringContainsString('CNAME', $display);
        $this->assertStringContainsString('selector1.dkim.amazonses.com', $display);
    }

    public function testGetEmailIdentityInfoWithName(): void
    {
        $team = $this->setupActiveTeam();

        $identity = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
            'type' => 'domain',
            'verified' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity]));

        $this->bootApplication([new GetEmailIdentityInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            EmailIdentity::class => function () {
                return new EmailIdentityDefinition();
            },
        ]))]);

        $tester = $this->executeCommand(GetEmailIdentityInfoCommand::NAME, ['identity' => 'example.com']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('example.com', $display);
    }
}
