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

use Ymir\Cli\Console\ChoiceQuestion;
use Ymir\Cli\Tests\TestCase;

class ChoiceQuestionTest extends TestCase
{
    public function testIsAssocReturnsTrueIfZeroIndexNotSet(): void
    {
        $question = new ChoiceQuestion('foo', ['bar' => 'baz']);

        $reflection = new \ReflectionClass(ChoiceQuestion::class);
        $method = $reflection->getMethod('isAssoc');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($question, ['bar' => 'baz']));
        $this->assertFalse($method->invoke($question, ['bar', 'baz']));
        $this->assertTrue($method->invoke($question, [1 => 'bar']));
    }
}
