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

use Ymir\Cli\Command\Php\PhpInfoCommand;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class PhpInfoCommandTest extends TestCase
{
    public function testPhpInfoInteractive(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['production' => []]);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => '--info'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'php info output',
            ],
        ]));

        $this->bootApplication([new PhpInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(PhpInfoCommand::NAME, [], ['production']);

        $this->assertStringContainsString('Getting information about PHP from the "production" environment', $tester->getDisplay());
        $this->assertStringContainsString('php info output', $tester->getDisplay());
    }

    public function testPhpInfoWithArgument(): void
    {
        $project = $this->setupValidProject(1, 'my-project', ['staging' => []]);
        $environment = EnvironmentFactory::create(['name' => 'staging']);

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->apiClient->shouldReceive('createInvocation')->with(\Mockery::type(Project::class), $environment, ['php' => '--info'])->andReturn(collect(['id' => 123]));
        $this->apiClient->shouldReceive('getInvocation')->with(123)->andReturn(collect([
            'status' => 'completed',
            'result' => [
                'exitCode' => 0,
                'output' => 'php info output',
            ],
        ]));

        $this->bootApplication([new PhpInfoCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]))]);

        $tester = $this->executeCommand(PhpInfoCommand::NAME, ['environment' => 'staging']);

        $this->assertStringContainsString('Getting information about PHP from the "staging" environment', $tester->getDisplay());
        $this->assertStringContainsString('php info output', $tester->getDisplay());
    }
}
