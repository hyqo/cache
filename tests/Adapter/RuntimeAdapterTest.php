<?php

namespace Hyqo\Cache\Test\Adapter;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Test\Fixtures\ImmodestRuntimeAdapter;
use PHPUnit\Framework\TestCase;

class RuntimeAdapterTest extends TestCase
{
    public function test_get_miss(): void
    {
        $pool = new ImmodestRuntimeAdapter();

        $key = 'foo';
        $newValue = 'baz';

        $this->assertArrayNotHasKey($key, $pool->storage());

        $item = $pool->getItem($key, fn(CacheItem $item) => $item->set($newValue));

        $this->assertFalse($item->isHit);
        $this->assertEquals($newValue, $item->get());

        $this->assertEquals([null, $newValue], $pool->storage()[$key]);
    }
}
