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

use Ymir\Cli\Project\Configuration\DatabaseConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class DatabaseConfigurationChangeTest extends TestCase
{
    public function testApplyWithoutPrefix(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $configurationChange = new DatabaseConfigurationChange('server');

        $this->assertSame([
            'database' => [
                'server' => 'server',
            ],
        ], $configurationChange->apply(new EnvironmentConfiguration('staging'), $projectType)->toArray());
    }

    public function testApplyWithPrefix(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $configurationChange = new DatabaseConfigurationChange('server', 'prefix_');

        $this->assertSame([
            'database' => [
                'server' => 'server',
                'name' => 'prefix_staging',
            ],
        ], $configurationChange->apply(new EnvironmentConfiguration('staging'), $projectType)->toArray());
    }
}
