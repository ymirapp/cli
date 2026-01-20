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

namespace Ymir\Cli\Tests\Unit\Console;

use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\HiddenInputOption;
use Ymir\Cli\Console\InputDefinition;
use Ymir\Cli\Tests\TestCase;

class InputDefinitionTest extends TestCase
{
    public function testGetOptionsFiltersHiddenInputOptions(): void
    {
        $option1 = new InputOption('foo');
        $option2 = new HiddenInputOption('bar');

        $definition = new InputDefinition([$option1, $option2]);

        $options = $definition->getOptions();

        $this->assertCount(1, $options);
        $this->assertArrayHasKey('foo', $options);
        $this->assertArrayNotHasKey('bar', $options);
    }
}
