<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheChain;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Exception\CacheException;
use Hyqo\Cache\Test\Fixtures\ImmodestRuntimeAdapter;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;

class CacheChainTest extends TestCase
{
    protected static int $timestamp;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$timestamp = time();
    }

    #[ArrayShape([CacheChain::class, ImmodestRuntimeAdapter::class, ImmodestRuntimeAdapter::class])]
    protected function mock(): array
    {
        $foo = new ImmodestRuntimeAdapter();
        $bar = new ImmodestRuntimeAdapter();

        $bar->storage()['partial'] = [self::$timestamp + 10, 'partial value'];
        $foo->storage()['expired'] = $bar->storage()['expired'] = [self::$timestamp - 10, ''];

        $foo->storage()['abc'] = [null, 'foo value'];
        $bar->storage()['abc'] = [null, 'bar value'];

        $chain = new CacheChain([$foo, $bar]);

        return [$chain, $foo, $bar];
    }

    public function test_empty_chain(): void
    {
        $this->expectException(CacheException::class);
        new CacheChain([]);
    }

    public function test_has(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'partial';

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $this->assertTrue($chain->hasItem($key));
        $this->assertFalse($chain->hasItem('bar'));
    }

    public function test_get_partial_hit(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'partial';
        $value = 'partial value';

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $item = $chain->getItem($key);

        $this->assertTrue($item->isHit);
        $this->assertEquals($value, $item->get());
        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertEquals([$item->getExpiresAt(), $value], $foo->storage()[$key]);
    }

    public function test_get_nonexistent(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'nonexistent';
        $value = 'nonexistent value';
        $expiresAt = self::$timestamp + 10;

        $this->assertArrayNotHasKey($key, $bar->storage());
        $this->assertArrayNotHasKey($key, $foo->storage());

        $item = $chain->getItem($key, fn(CacheItem $item) => $item->set($value)->expiresAt($expiresAt));

        $this->assertFalse($item->isHit);
        $this->assertEquals($value, $item->get());

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertEquals([$expiresAt, $value], $foo->storage()[$key]);

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertEquals([$expiresAt, $value], $bar->storage()[$key]);
    }

    public function test_get_miss(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'expired';
        $currentValue = '';
        $newValue = 'v';
        $currentExpiresAt = self::$timestamp - 10;
        $newExpiresAt = self::$timestamp + 10;

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $this->assertEquals([$currentExpiresAt, $currentValue], $foo->storage()[$key]);
        $this->assertEquals([$currentExpiresAt, $currentValue], $bar->storage()[$key]);

        $item = $chain->getItem($key, fn(CacheItem $item) => $item->set($newValue)->expiresAt($newExpiresAt));

        $this->assertFalse($item->isHit);
        $this->assertEquals($newValue, $item->get());

        $this->assertEquals([$newExpiresAt, $newValue], $foo->storage()[$key]);
        $this->assertEquals([$newExpiresAt, $newValue], $bar->storage()[$key]);
    }

    public function test_get_hit(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'abc';
        $expiresAt = null;

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $this->assertEquals([$expiresAt, 'foo value'], $foo->storage()[$key]);
        $this->assertEquals([$expiresAt, 'bar value'], $bar->storage()[$key]);

        $item = $chain->getItem($key);

        $this->assertTrue($item->isHit);
        $this->assertEquals('foo value', $item->get());
    }

    public function test_save(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'new';
        $value = 'v';
        $expiresAt = null;

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayNotHasKey($key, $bar->storage());

        $item = (new CacheItem($key))->set($value);

        $chain->save($item);

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $this->assertEquals([$expiresAt, $value], $foo->storage()[$key]);
        $this->assertEquals([$expiresAt, $value], $bar->storage()[$key]);
    }

    public function test_delete(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'partial';

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $chain->delete($key);

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayNotHasKey($key, $bar->storage());
    }

    public function test_flush(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $this->assertNotEmpty($foo->storage());
        $this->assertNotEmpty($bar->storage());

        $chain->flush();

        $this->assertEmpty($foo->storage());
        $this->assertEmpty($bar->storage());
    }
}
