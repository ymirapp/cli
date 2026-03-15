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

namespace Ymir\Cli\Tests\Unit;

use Ymir\Cli\Exception\YamlParseException;
use Ymir\Cli\Tests\TestCase;
use Ymir\Cli\YamlParser;

class YamlParserTest extends TestCase
{
    /**
     * @var string
     */
    private $tempFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'ymir-yaml-parser-test');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        parent::tearDown();
    }

    public function testParseReturnsArray(): void
    {
        file_put_contents($this->tempFile, "foo: bar\nbaz: 123");

        $this->assertSame(['foo' => 'bar', 'baz' => 123], (new YamlParser())->parse($this->tempFile));
    }

    public function testParseReturnsEmptyArrayWhenFileIsEmpty(): void
    {
        file_put_contents($this->tempFile, '');

        $this->assertSame([], (new YamlParser())->parse($this->tempFile));
    }

    public function testParseReturnsNullWhenFileDoesNotExist(): void
    {
        unlink($this->tempFile);

        $this->assertNull((new YamlParser())->parse($this->tempFile));
    }

    public function testParseThrowsExceptionWhenParsedYamlIsNotArray(): void
    {
        file_put_contents($this->tempFile, 'foo');

        $this->expectException(YamlParseException::class);
        $this->expectExceptionMessage('Error parsing YAML file at');

        (new YamlParser())->parse($this->tempFile);
    }

    public function testParseThrowsExceptionWhenYamlIsInvalid(): void
    {
        file_put_contents($this->tempFile, 'invalid yaml: [');

        $this->expectException(YamlParseException::class);
        $this->expectExceptionMessage('Error parsing YAML file at');

        (new YamlParser())->parse($this->tempFile);
    }
}
