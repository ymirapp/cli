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

namespace Ymir\Cli\Tests\Unit\Resource;

use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\TestCase;

class ResourceCollectionTest extends TestCase
{
    public function testFirstWhereId(): void
    {
        $resource1 = SecretFactory::create(['id' => 1]);
        $resource2 = SecretFactory::create(['id' => 2]);

        $collection = new ResourceCollection([$resource1, $resource2]);

        $this->assertSame($resource2, $collection->firstWhereId(2));
        $this->assertNull($collection->firstWhereId(3));
    }

    public function testFirstWhereIdOrName(): void
    {
        $resource1 = SecretFactory::create(['id' => 1, 'name' => 'name1']);
        $resource2 = SecretFactory::create(['id' => 2, 'name' => 'name2']);

        $collection = new ResourceCollection([$resource1, $resource2]);

        $this->assertSame($resource1, $collection->firstWhereIdOrName('1'));
        $this->assertSame($resource2, $collection->firstWhereIdOrName('name2'));
        $this->assertNull($collection->firstWhereIdOrName('3'));
        $this->assertNull($collection->firstWhereIdOrName('name3'));
    }

    public function testFirstWhereName(): void
    {
        $resource1 = SecretFactory::create(['name' => 'name1']);
        $resource2 = SecretFactory::create(['name' => 'name2']);

        $collection = new ResourceCollection([$resource1, $resource2]);

        $this->assertSame($resource2, $collection->firstWhereName('name2'));
        $this->assertNull($collection->firstWhereName('name3'));
    }

    public function testWhereIdOrName(): void
    {
        $resource1 = SecretFactory::create(['id' => 1, 'name' => 'name1']);
        $resource2 = SecretFactory::create(['id' => 2, 'name' => 'name2']);

        $collection = new ResourceCollection([$resource1, $resource2]);

        $filtered = $collection->whereIdOrName('1');
        $this->assertCount(1, $filtered);
        $this->assertSame($resource1, $filtered->first());

        $filtered = $collection->whereIdOrName('name2');
        $this->assertCount(1, $filtered);
        $this->assertSame($resource2, $filtered->first());
    }

    public function testWhereName(): void
    {
        $resource1 = SecretFactory::create(['name' => 'name1']);
        $resource2 = SecretFactory::create(['name' => 'name2']);

        $collection = new ResourceCollection([$resource1, $resource2]);

        $filtered = $collection->whereName('name2');
        $this->assertCount(1, $filtered);
        $this->assertSame($resource2, $filtered->first());
    }
}
