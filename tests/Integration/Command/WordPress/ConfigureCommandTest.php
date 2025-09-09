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

namespace Ymir\Cli\Tests\Integration\Command\WordPress;

use Ymir\Cli\Command\WordPress\ConfigureCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\Project\Configuration\WordPress\WordPressConfigurationChangeInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ConfigureCommandTest extends TestCase
{
    private $wpCliExecutable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wpCliExecutable = \Mockery::mock(WpCliExecutable::class);
    }

    public function testPerformScansAndAppliesChanges(): void
    {
        $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $this->wpCliExecutable->shouldReceive('isInstalled')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('listPlugins')->andReturn(collect([
            ['name' => 'akismet', 'status' => 'active', 'title' => 'Akismet Anti-Spam'],
        ]));

        $environmentConfig = new EnvironmentConfiguration('production', []);
        $change = \Mockery::mock(WordPressConfigurationChangeInterface::class);
        $change->shouldReceive('getName')->andReturn('akismet');
        $change->shouldReceive('apply')->once()->andReturn($environmentConfig);

        $this->bootApplication([new ConfigureCommand($this->apiClient, $this->createExecutionContextFactory(), $this->wpCliExecutable, [$change])]);

        $tester = $this->executeCommand(ConfigureCommand::NAME, [], ['yes']);

        $this->assertStringContainsString('Scanning your project', $tester->getDisplay());
        $this->assertStringContainsString('The following plugin(s) are active and have available configuration changes:', $tester->getDisplay());
        $this->assertStringContainsString('Akismet Anti-Spam', $tester->getDisplay());
        $this->assertStringContainsString('Project configuration updated successfully', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfEnvironmentNotFound(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Environment "staging" not found in ymir.yml file');

        $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $this->wpCliExecutable->shouldReceive('isInstalled')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->andReturn(true);

        $this->bootApplication([new ConfigureCommand($this->apiClient, $this->createExecutionContextFactory(), $this->wpCliExecutable)]);

        $this->executeCommand(ConfigureCommand::NAME, ['environments' => ['staging']]);
    }

    public function testPerformThrowsExceptionIfProjectIsNotWordPress(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('You can only use this command with WordPress, Bedrock or Radicle projects');

        $this->setupValidProject(1, 'project', [], 'laravel');

        $this->bootApplication([new ConfigureCommand($this->apiClient, $this->createExecutionContextFactory(), $this->wpCliExecutable)]);

        $this->executeCommand(ConfigureCommand::NAME);
    }

    public function testPerformThrowsExceptionIfWordPressIsNotInstalled(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('WordPress must be installed and connected to a database to use this command');

        $this->setupValidProject(1, 'project', [], 'wordpress', WordPressProjectType::class);
        $this->wpCliExecutable->shouldReceive('isInstalled')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->andReturn(false);

        $this->bootApplication([new ConfigureCommand($this->apiClient, $this->createExecutionContextFactory(), $this->wpCliExecutable)]);

        $this->executeCommand(ConfigureCommand::NAME);
    }

    public function testPerformThrowsExceptionIfWpCliIsNotInstalled(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('WP-CLI must be available globally to use this command');

        $this->setupValidProject(1, 'project', [], 'wordpress', WordPressProjectType::class);
        $this->wpCliExecutable->shouldReceive('isInstalled')->andReturn(false);

        $this->bootApplication([new ConfigureCommand($this->apiClient, $this->createExecutionContextFactory(), $this->wpCliExecutable)]);

        $this->executeCommand(ConfigureCommand::NAME);
    }
}
