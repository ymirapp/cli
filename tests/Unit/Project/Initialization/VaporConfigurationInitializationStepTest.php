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

namespace Ymir\Cli\Tests\Unit\Project\Initialization;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\YamlParseException;
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Laravel\VaporDockerfileMigrator;
use Ymir\Cli\Project\Configuration\Laravel\VaporConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Initialization\VaporConfigurationInitializationStep;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;
use Ymir\Cli\YamlParser;

class VaporConfigurationInitializationStepTest extends TestCase
{
    /**
     * @var ExecutionContext|\Mockery\MockInterface
     */
    private $context;

    /**
     * @var \Mockery\MockInterface|Output
     */
    private $output;

    /**
     * @var string
     */
    private $projectDirectory;

    /**
     * @var \Mockery\MockInterface|VaporDockerfileMigrator
     */
    private $vaporDockerfileMigrator;

    /**
     * @var \Mockery\MockInterface|YamlParser
     */
    private $yamlParser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->output = \Mockery::mock(Output::class);
        $this->projectDirectory = '/path/to/project';
        $this->vaporDockerfileMigrator = \Mockery::mock(VaporDockerfileMigrator::class);
        $this->yamlParser = \Mockery::mock(YamlParser::class);

        $this->context->shouldReceive('getOutput')->andReturn($this->output);
        $this->context->shouldReceive('getProjectDirectory')->andReturn($this->projectDirectory);
    }

    public function testPerformMigratesEnvironmentDockerfilesWhenGlobalDockerfileIsDeclined(): void
    {
        $projectType = $this->createLaravelProjectType();

        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Vapor migration affects image deployment environments. Do you also want to migrate/create Dockerfile(s) now?', false)->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Do you want to create one global <comment>Dockerfile</comment> for all image deployment environments?', false)->andReturn(false);
        $this->vaporDockerfileMigrator->shouldReceive('migrateEnvironmentDockerfiles')->once()->with(\Mockery::on(function ($environmentConfigurations): bool {
            return $environmentConfigurations instanceof \Illuminate\Support\Collection
                && 1 === $environmentConfigurations->count()
                && 'production' === $environmentConfigurations->first()->getName();
        }), '/path/to/project', $projectType)->andReturn([
            'created_dockerfiles' => [
                [
                    'name' => 'production.Dockerfile',
                    'php_version' => '8.3',
                    'architecture' => 'arm64',
                ],
            ],
            'backed_up_dockerfile_paths' => [],
        ]);
        $this->output->shouldReceive('info')->once()->with('Created <comment>production.Dockerfile</comment> for PHP <comment>8.3</comment> and <comment>arm64</comment> architecture');
        $this->output->shouldReceive('info')->once()->with('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->shouldReceive('list')->once()->with(\Mockery::on(function ($environments): bool {
            return $environments instanceof \Illuminate\Support\Collection && ['production'] === $environments->all();
        }));

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $result = $step->perform($this->context, [
            'type' => $projectType,
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production', [
                    'deployment' => [
                        'type' => 'image',
                    ],
                ]),
            ]),
        ]);

        $this->assertInstanceOf(VaporConfigurationChange::class, $result);
    }

    public function testPerformMigratesGlobalDockerfileWhenRequested(): void
    {
        $projectType = $this->createLaravelProjectType();

        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Vapor migration affects image deployment environments. Do you also want to migrate/create Dockerfile(s) now?', false)->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Do you want to create one global <comment>Dockerfile</comment> for all image deployment environments?', false)->andReturn(true);
        $this->vaporDockerfileMigrator->shouldReceive('migrateGlobalDockerfile')->once()->with(\Mockery::on(function ($environmentConfigurations): bool {
            return $environmentConfigurations instanceof \Illuminate\Support\Collection
                && 1 === $environmentConfigurations->count()
                && 'production' === $environmentConfigurations->first()->getName();
        }), '/path/to/project', $projectType)->andReturn([
            'created_dockerfiles' => [
                [
                    'name' => 'Dockerfile',
                    'php_version' => '8.4',
                    'architecture' => 'x86_64',
                ],
            ],
            'backed_up_dockerfile_paths' => [],
        ]);
        $this->output->shouldReceive('info')->once()->with('Created <comment>Dockerfile</comment> for PHP <comment>8.4</comment> and <comment>x86_64</comment> architecture');
        $this->output->shouldReceive('info')->once()->with('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->shouldReceive('list')->once()->with(\Mockery::on(function ($environments): bool {
            return $environments instanceof \Illuminate\Support\Collection && ['production'] === $environments->all();
        }));

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $result = $step->perform($this->context, [
            'type' => $projectType,
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production', [
                    'deployment' => [
                        'type' => 'image',
                    ],
                ]),
            ]),
        ]);

        $this->assertInstanceOf(VaporConfigurationChange::class, $result);
    }

    public function testPerformReturnsNullAndWarnsWhenNoMatchingEnvironmentsAreFound(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('warning')->once()->with('No matching environments found between ymir.yml and vapor.yml files');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => collect([
                'staging' => new EnvironmentConfiguration('staging'),
            ]),
        ]));
    }

    public function testPerformReturnsNullAndWarnsWhenVaporEnvironmentsKeyIsInvalid(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => 'invalid',
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('warning')->once()->with('No valid "environments" key found in vapor.yml file');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production'),
            ]),
        ]));
    }

    public function testPerformReturnsNullAndWarnsWhenYamlCannotBeParsed(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andThrow(new YamlParseException('yaml parse error'));
        $this->output->shouldReceive('warning')->once()->with('yaml parse error');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, ['type' => $this->createLaravelProjectType()]));
    }

    public function testPerformReturnsNullIfProjectTypeIsMissingOrNotLaravel(): void
    {
        $this->yamlParser->shouldNotReceive('parse');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, []));
        $this->assertNull($step->perform($this->context, ['type' => \Mockery::mock(ProjectTypeInterface::class)]));
    }

    public function testPerformReturnsNullIfUserDeclinesVaporMigration(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(false);

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production'),
            ]),
        ]));
    }

    public function testPerformReturnsNullWhenEnvironmentConfigurationsAreNotACollection(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => [],
        ]));
    }

    public function testPerformReturnsNullWhenVaporConfigurationFileIsMissing(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn(null);
        $this->output->shouldNotReceive('confirm');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $this->assertNull($step->perform($this->context, ['type' => $this->createLaravelProjectType()]));
    }

    public function testPerformReturnsVaporConfigurationChangeWhenNoImageDeploymentsNeedDockerfileMigration(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [
                    'memory' => 1024,
                ],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('info')->once()->with('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->shouldReceive('list')->once()->with(\Mockery::on(function ($environments): bool {
            return $environments instanceof \Illuminate\Support\Collection && ['production'] === $environments->all();
        }));
        $this->vaporDockerfileMigrator->shouldNotReceive('migrateGlobalDockerfile');
        $this->vaporDockerfileMigrator->shouldNotReceive('migrateEnvironmentDockerfiles');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $result = $step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production', [
                    'deployment' => [
                        'type' => 'deployment-type',
                    ],
                ]),
            ]),
        ]);

        $this->assertInstanceOf(VaporConfigurationChange::class, $result);
    }

    public function testPerformSkipsDockerfileMigrationWhenUserDeclinesDockerfilePrompt(): void
    {
        $this->yamlParser->shouldReceive('parse')->once()->with('/path/to/project/vapor.yml')->andReturn([
            'environments' => [
                'production' => [],
            ],
        ]);
        $this->output->shouldReceive('confirm')->once()->with('Detected <comment>vapor.yml</comment>. Do you want to migrate matching Vapor settings into <comment>ymir.yml</comment>?', true)->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Vapor migration affects image deployment environments. Do you also want to migrate/create Dockerfile(s) now?', false)->andReturn(false);
        $this->output->shouldReceive('info')->once()->with('Vapor configuration migrated into <comment>ymir.yml</comment> file for the following environment(s):');
        $this->output->shouldReceive('list')->once();
        $this->vaporDockerfileMigrator->shouldNotReceive('migrateGlobalDockerfile');
        $this->vaporDockerfileMigrator->shouldNotReceive('migrateEnvironmentDockerfiles');

        $step = new VaporConfigurationInitializationStep($this->vaporDockerfileMigrator, $this->yamlParser);

        $result = $step->perform($this->context, [
            'type' => $this->createLaravelProjectType(),
            'environment_configurations' => collect([
                'production' => new EnvironmentConfiguration('production', [
                    'deployment' => [
                        'type' => 'image',
                    ],
                ]),
            ]),
        ]);

        $this->assertInstanceOf(VaporConfigurationChange::class, $result);
    }

    private function createLaravelProjectType(): LaravelProjectType
    {
        return new LaravelProjectType(\Mockery::mock(ComposerExecutable::class), \Mockery::mock(Filesystem::class));
    }
}
