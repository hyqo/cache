<?php

namespace Hyqo\Cache\Test\Adapter;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Test\Fixtures\ImmodestRuntimeAdapter;
use PHPUnit\Framework\TestCase;

class RuntimeAdapterTest extends TestCase
{
    public function test_get_miss(): void
    {
        $expiresAt = time()+10;
        $pool = new ImmodestRuntimeAdapter(10);

        $key = 'foo';
        $newValue = 'baz';

        $this->assertArrayNotHasKey($key, $pool->storage());

        $item = $pool->get($key, fn(CacheItem $item) => $newValue);

        $this->assertFalse($item->isHit());
        $this->assertEquals($newValue, $item->get());
        $this->assertEquals($expiresAt, $item->getExpiresAt());

        $this->assertEquals([$expiresAt, $newValue], $pool->storage()[$key]);
    }
}
