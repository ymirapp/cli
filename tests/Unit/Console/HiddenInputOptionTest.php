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
use Ymir\Cli\Tests\TestCase;

class HiddenInputOptionTest extends TestCase
{
    public function testConstructorSetsEmptyDescription(): void
    {
        $option = new HiddenInputOption('foo', 'f', InputOption::VALUE_REQUIRED, 'bar');

        $this->assertSame('foo', $option->getName());
        $this->assertSame('f', $option->getShortcut());
        $this->assertTrue($option->isValueRequired());
        $this->assertSame('', $option->getDescription());
        $this->assertSame('bar', $option->getDefault());
    }
}
