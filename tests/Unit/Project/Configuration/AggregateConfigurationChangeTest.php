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

use Ymir\Cli\Project\Configuration\AggregateConfigurationChange;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class AggregateConfigurationChangeTest extends TestCase
{
    public function testApplyAppliesAllConfigurationChanges(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['foo' => 'bar']);

        $change1 = \Mockery::mock(ConfigurationChangeInterface::class);
        $change2 = \Mockery::mock(ConfigurationChangeInterface::class);

        $environmentConfiguration1 = new EnvironmentConfiguration('prod', ['foo' => 'bar', 'change1' => true]);
        $environmentConfiguration2 = new EnvironmentConfiguration('prod', ['foo' => 'bar', 'change1' => true, 'change2' => true]);

        $change1->shouldReceive('apply')->once()->with($environmentConfiguration, $projectType)->andReturn($environmentConfiguration1);
        $change2->shouldReceive('apply')->once()->with($environmentConfiguration1, $projectType)->andReturn($environmentConfiguration2);

        $aggregateChange = new AggregateConfigurationChange([$change1, $change2]);

        $this->assertSame($environmentConfiguration2, $aggregateChange->apply($environmentConfiguration, $projectType));
    }
}
