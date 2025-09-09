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

namespace Ymir\Cli\Tests\Integration\Command\Php;

use Ymir\Cli\Command\Php\PhpVersionCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class PhpVersionCommandTest extends TestCase
{
    public function testPhpVersionInteractive(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => '--version'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'PHP 8.1.0 (cli)',
            ],
        ]));

        $this->bootApplication([new PhpVersionCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(PhpVersionCommand::NAME, [], ['production']);

        $this->assertStringContainsString('Getting PHP version information from the "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('PHP 8.1.0 (cli)', $tester->getDisplay());
    }

    public function testPhpVersionWithArgument(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => '--version'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'PHP 8.1.0 (cli)',
            ],
        ]));

        $this->bootApplication([new PhpVersionCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(PhpVersionCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Getting PHP version information from the "staging" environment', $tester->getDisplay());
        $this->assertStringContainsString('PHP 8.1.0 (cli)', $tester->getDisplay());
    }
}
