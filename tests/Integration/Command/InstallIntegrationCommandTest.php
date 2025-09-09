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

use Ymir\Cli\Command\InstallIntegrationCommand;

class InstallIntegrationCommandTest extends TestCase
{
    public function testInstallIntegrationSkipsIfAlreadyInstalled(): void
    {
        $this->setupValidProject();
        $this->projectTypeMock->shouldReceive('isIntegrationInstalled')->with($this->tempDir)->andReturn(true);
        $this->projectTypeMock->shouldReceive('getName')->andReturn('WordPress');

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new InstallIntegrationCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(InstallIntegrationCommand::NAME);

        $this->assertStringContainsString('The Ymir integration for WordPress is already installed', $tester->getDisplay());
    }

    public function testInstallIntegrationSuccessfully(): void
    {
        $this->setupValidProject();
        $this->projectTypeMock->shouldReceive('isIntegrationInstalled')->with($this->tempDir)->andReturn(false);
        $this->projectTypeMock->shouldReceive('getName')->andReturn('WordPress')->twice();
        $this->projectTypeMock->shouldReceive('installIntegration')->with($this->tempDir)->once();

        $contextFactory = $this->createExecutionContextFactory();

        $this->bootApplication([new InstallIntegrationCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(InstallIntegrationCommand::NAME);

        $this->assertStringContainsString('Installing the Ymir integration for WordPress', $tester->getDisplay());
        $this->assertStringContainsString('Ymir WordPress integration installed successfully', $tester->getDisplay());
    }
}
