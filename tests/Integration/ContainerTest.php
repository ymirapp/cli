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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Ymir\Cli\Application;
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
}
