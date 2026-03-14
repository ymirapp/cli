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

namespace Ymir\Cli\Tests\Unit\Project\Configuration;

use Ymir\Cli\Project\Configuration\VaporConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class VaporConfigurationChangeTest extends TestCase
{
    public static function provideDirectMappings(): array
    {
        return [
            ['build', ['npm ci'], ['build' => ['commands' => ['npm ci']]]],
            ['cli-timeout', 120, ['console' => ['timeout' => 120]]],
            ['database', 'server-name', ['database' => ['server' => 'server-name']]],
            ['database-user', 'database-user', ['database' => ['user' => 'database-user']]],
            ['domain', 'example.com', ['domain' => 'example.com']],
            ['firewall.bot-control', true, ['firewall' => ['bots' => true]]],
            ['firewall.rate-limit', 100, ['firewall' => ['rate_limit' => 100]]],
            ['memory', 1024, ['memory' => 1024]],
            ['warm', 5, ['warmup' => 5]],
            ['concurrency', 25, ['website' => ['concurrency' => 25]]],
            ['timeout', 30, ['website' => ['timeout' => 30]]],
        ];
    }

    public function testApplyAddsDeploymentCommandsAndPreservesNestedDeploymentType(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'deploy' => ['php artisan migrate --force'],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'deployment' => [
                'type' => 'deployment-type',
            ],
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'type' => 'deployment-type',
                'commands' => ['php artisan migrate --force'],
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyAddsDeploymentCommandsAndPreservesRootDeploymentType(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'deploy' => ['php artisan migrate --force'],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', ['deployment' => 'deployment-type']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'type' => 'deployment-type',
                'commands' => ['php artisan migrate --force'],
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyAddsDeploymentCommandsWithoutDeploymentType(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'deploy' => ['php artisan migrate --force'],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging');

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'commands' => ['php artisan migrate --force'],
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyDoesNotChangeDeploymentWhenDeployCommandsAreEmpty(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'deploy' => [],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', ['deployment' => 'deployment-type']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => 'deployment-type',
        ], $environmentConfiguration->toArray());
    }

    public function testApplyDoesNothingWhenDeployCommandsAreEmpty(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'deploy' => [],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'deployment' => [
                'type' => 'deployment-type',
            ],
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'type' => 'deployment-type',
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyDoesNothingWhenEnvironmentNodeIsNotAnArray(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => 'invalid',
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', ['foo' => 'bar']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'foo' => 'bar',
        ], $environmentConfiguration->toArray());
    }

    public function testApplyDoesNotOverrideDirectMappingWhenValueIsNull(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'domain' => null,
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', ['domain' => 'existing-domain.com']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'domain' => 'existing-domain.com',
        ], $environmentConfiguration->toArray());
    }

    public function testApplyDoesNotSetPhpVersionForImageDeployment(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'runtime' => 'php-8.3-arm',
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'deployment' => [
                'type' => 'image',
            ],
            'php' => '8.1',
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'type' => 'image',
            ],
            'php' => '8.1',
            'architecture' => 'arm64',
        ], $environmentConfiguration->toArray());
    }

    /**
     * @dataProvider provideDirectMappings
     */
    public function testApplyMapsDirectConfigurationValues(string $vaporKey, $value, array $expected): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    $vaporKey => $value,
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging');

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame($expected, $environmentConfiguration->toArray());
    }

    public function testApplyMergesQueueDefaultsIntoNamedQueues(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'queue-concurrency' => '5',
                    'queue-memory' => 512,
                    'queue-timeout' => 60,
                    'queues' => [
                        'emails',
                        'invoices.fifo' => 3,
                    ],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging');

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'queues' => [
                'emails' => [
                    'concurrency' => 5,
                    'memory' => 512,
                    'timeout' => 60,
                ],
                'invoices' => [
                    'concurrency' => 3,
                    'memory' => 512,
                    'timeout' => 60,
                    'type' => 'fifo',
                ],
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyParsesSingleQueueObjectSyntax(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'queues' => [
                        ['reports' => 4],
                    ],
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging');

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'queues' => [
                'reports' => [
                    'concurrency' => 4,
                ],
            ],
        ], $environmentConfiguration->toArray());
    }

    public function testApplyRemovesQueuesWhenVaporQueuesDisabled(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'queues' => false,
                    'queue-concurrency' => 5,
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'memory' => 512,
            'queues' => [
                'default' => ['concurrency' => 10],
            ],
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'memory' => 512,
        ], $environmentConfiguration->toArray());
    }

    public function testApplySetsArchitectureAndPhpFromRuntimeWhenNotImageDeployment(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'runtime' => 'php-8.2-arm',
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'deployment' => [
                'type' => 'deployment-type',
            ],
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'deployment' => [
                'type' => 'deployment-type',
            ],
            'architecture' => 'arm64',
            'php' => '8.2',
        ], $environmentConfiguration->toArray());
    }

    public function testApplySetsCronToFalseWhenSchedulerDisabled(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'scheduler' => false,
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging', [
            'cron' => true,
        ]);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'cron' => false,
        ], $environmentConfiguration->toArray());
    }

    public function testApplySetsQueueDefaultsWhenOnlyQueueDefaultsAreConfigured(): void
    {
        $change = new VaporConfigurationChange([
            'environments' => [
                'staging' => [
                    'queue-concurrency' => '5',
                    'queue-memory' => 512,
                    'queue-timeout' => 60,
                ],
            ],
        ]);
        $environmentConfiguration = new EnvironmentConfiguration('staging');

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame([
            'queues' => [
                'concurrency' => 5,
                'memory' => 512,
                'timeout' => 60,
            ],
        ], $environmentConfiguration->toArray());
    }
}
