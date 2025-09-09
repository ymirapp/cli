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

use Ymir\Cli\Command\Email\ListEmailIdentitiesCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EmailIdentityFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListEmailIdentitiesCommandTest extends TestCase
{
    public function testListEmailIdentities(): void
    {
        $team = $this->setupActiveTeam();

        $identity1 = EmailIdentityFactory::create([
            'id' => 1,
            'name' => 'example.com',
            'type' => 'domain',
            'verified' => true,
            'managed' => false,
        ]);
        $identity2 = EmailIdentityFactory::create([
            'id' => 2,
            'name' => 'user@example.com',
            'type' => 'email',
            'verified' => false,
            'managed' => true,
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getEmailIdentities')->with($team)->andReturn(new ResourceCollection([$identity1, $identity2]));

        $this->bootApplication([new ListEmailIdentitiesCommand($this->apiClient, $this->createExecutionContextFactory())]);

        $tester = $this->executeCommand(ListEmailIdentitiesCommand::NAME);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('1', $display);
        $this->assertStringContainsString('example.com', $display);
        $this->assertStringContainsString('domain', $display);
        $this->assertStringContainsString('yes', $display); // verified
        $this->assertStringContainsString('no', $display);  // managed

        $this->assertStringContainsString('2', $display);
        $this->assertStringContainsString('user@example.com', $display);
        $this->assertStringContainsString('email', $display);
        $this->assertStringContainsString('no', $display);  // verified
        $this->assertStringContainsString('yes', $display); // managed
    }
}
