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

namespace Ymir\Cli\Tests\Integration;

use Carbon\Carbon;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Application;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\WarnEndOfLifePhpVersionStep;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerBuilds(): void
    {
        $applicationDirectory = dirname(__DIR__, 2);
        $container = new ContainerBuilder();

        $container->setParameter('application_directory', 'application_directory');
        $container->setParameter('home_directory', 'home_directory');
        $container->setParameter('vendor_directory', $applicationDirectory.'/vendor');
        $container->setParameter('working_directory', 'working_directory');
        $container->setParameter('ymir_api_url', 'ymir_api_url');

        (new YamlFileLoader($container, new FileLocator()))->load($applicationDirectory.'/config/services.yml');

        $container->compile();

        $this->assertTrue($container->has(Application::class));
    }

    public function testWarnEndOfLifePhpVersionStepUsesBuildDirectoryDockerfile(): void
    {
        $filesystem = new Filesystem();
        $projectDirectory = sys_get_temp_dir().'/ymir-container-test-'.uniqid();

        Carbon::setTestNow(Carbon::parse('2026-10-28', 'UTC'));

        try {
            $filesystem->dumpFile($projectDirectory.'/Dockerfile', "FROM ymirapp/php-runtime:php-83\n");
            $filesystem->dumpFile($projectDirectory.'/.ymir/build/Dockerfile', "FROM ymirapp/php-runtime:php-74\n");

            $applicationDirectory = dirname(__DIR__, 2);
            $container = new ContainerBuilder();

            $container->setParameter('application_directory', $applicationDirectory);
            $container->setParameter('home_directory', 'home_directory');
            $container->setParameter('vendor_directory', $applicationDirectory.'/vendor');
            $container->setParameter('working_directory', $projectDirectory);
            $container->setParameter('ymir_api_url', 'ymir_api_url');

            (new YamlFileLoader($container, new FileLocator()))->load($applicationDirectory.'/config/services.yml');

            $container->getDefinition(WarnEndOfLifePhpVersionStep::class)->setPublic(true);
            $container->compile();

            $context = \Mockery::mock(ExecutionContext::class);
            $output = \Mockery::mock(Output::class);

            $context->shouldReceive('getOutput')->andReturn($output);
            $output->shouldReceive('warning')->once()->with(\Mockery::pattern('/PHP <comment>7\.4<\/comment>/'));
            $output->shouldReceive('newLine')->once();

            $container->get(WarnEndOfLifePhpVersionStep::class)->perform($context, DeploymentFactory::create(['type' => 'deployment', 'configuration' => ['environments' => ['production' => ['deployment' => 'image']]]]), EnvironmentFactory::create(['name' => 'production']));
        } finally {
            Carbon::setTestNow();
            $filesystem->remove($projectDirectory);
        }
    }
}
