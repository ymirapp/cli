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

use Ymir\Cli\Project\Configuration\CacheConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class CacheConfigurationChangeTest extends TestCase
{
    public function testApplyAddsCacheNode(): void
    {
        $change = new CacheConfigurationChange('foo');
        $environmentConfiguration = new EnvironmentConfiguration('prod', ['bar' => 'baz']);

        $environmentConfiguration = $change->apply($environmentConfiguration, \Mockery::mock(ProjectTypeInterface::class));

        $this->assertSame(['bar' => 'baz', 'cache' => 'foo'], $environmentConfiguration->toArray());
    }
}
