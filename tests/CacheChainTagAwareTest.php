<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheChainTagAware;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Test\Fixtures\ImmodestRuntimeTagAwareAdapter;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;

class CacheChainTagAwareTest extends TestCase
{
    protected static int $timestamp;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$timestamp = time();
    }

    #[ArrayShape([
        CacheChainTagAware::class,
        ImmodestRuntimeTagAwareAdapter::class,
        ImmodestRuntimeTagAwareAdapter::class
    ])]
    protected function mock(): array
    {
        $foo = new ImmodestRuntimeTagAwareAdapter();
        $bar = new ImmodestRuntimeTagAwareAdapter(10);

        $foo->storage()['k'] = [null, ['tag3'], ''];
        $bar->storage()['k'] = [null, ['tag3'], ''];

        $bar->storage()['partial'] = [self::$timestamp + 10, ['tag1'], 'partial value'];
        $foo->storage()['expired'] = $bar->storage()['expired'] = [self::$timestamp - 10, ['tag1'], ''];

        $foo->storage()['abc'] = [null, ['tag1', 'tag2'], 'foo value'];
        $bar->storage()['abc'] = [null, ['tag1', 'tag2'], 'bar value'];

        $foo->tagStorage()['tag1'] = ['expired', 'abc'];
        $foo->tagStorage()['tag2'] = ['abc'];
        $foo->tagStorage()['tag3'] = ['k'];

        $bar->tagStorage()['tag1'] = ['partial', 'expired', 'abc'];
        $bar->tagStorage()['tag2'] = ['abc'];

        $chain = new CacheChainTagAware([$foo, $bar]);

        return [$chain, $foo, $bar];
    }

    public function test_save(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'new';
        $tags = ['tag1', 'tag3'];
        $value = 'v';
        $expiresAt = null;

        $this->assertArrayNotHasKey($key, $foo->storage());
        $this->assertArrayNotHasKey($key, $bar->storage());

        $item = (new CacheItem($key, false))->set($value)->tag($tags)->tag('tag1')->tag('tag2');

        $chain->save($item);

        $this->assertArrayHasKey($key, $foo->storage());
        $this->assertArrayHasKey($key, $bar->storage());

        $this->assertEquals([$expiresAt, [...$tags, 'tag2'], $value], $foo->storage()[$key]);
        $this->assertEquals([$expiresAt, [...$tags, 'tag2'], $value], $bar->storage()[$key]);

        $this->assertEquals([
            'tag1' => ['expired', 'abc', 'new'],
            'tag2' => ['abc', 'new'],
            'tag3' => ['k', 'new'],
        ], $foo->tagStorage());

        $this->assertEquals([
            'tag1' => ['partial', 'expired', 'abc', 'new'],
            'tag2' => ['abc', 'new'],
            'tag3' => ['new'],
        ], $bar->tagStorage());
    }

    public function test_save_tag(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $key = 'abc';
        $tag = 'tag4';

        $item = (new CacheItem($key, false))->tag($tag);

        $chain->save($item);

        $this->assertEquals([null, [$tag], null], $foo->storage()[$key]);
        $this->assertEquals([null, [$tag], null], $bar->storage()[$key]);

        $this->assertEquals([
            'tag1' => ['expired'],
            'tag3' => ['k'],
            'tag4' => ['abc'],
        ], $foo->tagStorage());

        $this->assertEquals([
            'tag1' => ['partial', 'expired'],
            'tag4' => ['abc'],
        ], $bar->tagStorage());
    }

    public function test_delete(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        foreach (['partial', 'abc', 'k'] as $key) {
            $chain->delete($key);
            $chain->delete($key);

            $this->assertArrayNotHasKey($key, $foo->storage());
            $this->assertArrayNotHasKey($key, $bar->storage());
        }

        $this->assertEquals([
            'tag1' => ['expired'],
        ], $foo->tagStorage());
    }

    public function test_flush(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $this->assertNotEmpty($foo->storage());
        $this->assertNotEmpty($bar->storage());

        $this->assertNotEmpty($foo->tagStorage());
        $this->assertNotEmpty($bar->tagStorage());

        $chain->flush();

        $this->assertEmpty($foo->storage());
        $this->assertEmpty($bar->storage());

        $this->assertEmpty($foo->tagStorage());
        $this->assertEmpty($bar->tagStorage());
    }

    public function test_flush_tag(): void
    {
        [$chain, $foo, $bar] = $this->mock();

        $this->assertNotEmpty($foo->storage());
        $this->assertNotEmpty($bar->storage());

        $chain->flushTag(['tag1']);

        $this->assertEquals([
            'tag3' => ['k'],
        ], $foo->tagStorage());

        $this->assertEquals([
        ], $bar->tagStorage());
    }
}
