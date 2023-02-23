<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheItem;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    public function test_miss(): void
    {
        $item = new CacheItem('foo', false);

        $this->assertEquals('foo', $item->getKey());
        $this->assertFalse($item->isHit);
        $this->assertNull($item->get());
    }

    public function test_hit(): void
    {
        $item = new CacheItem('foo', true, 'foo');

        $this->assertEquals('foo', $item->getKey());
        $this->assertTrue($item->isHit);
        $this->assertEquals('foo', $item->get());
    }

    public function test_expires_at(): void
    {
        $timestamp = time();
        $expiresAt = $timestamp + 100;
        $expiresAfter = $expiresAt - $timestamp;

        $item = new CacheItem('foo', true);
        $item->expiresAt($expiresAt);

        $this->assertEquals($expiresAt, $item->getExpiresAt());
        $this->assertEquals($expiresAfter, $item->getExpiresAfter());

        $item->expiresAt(null);

        $this->assertNull($item->getExpiresAt());
        $this->assertNull($item->getExpiresAfter());
    }

    public function test_expires_after(): void
    {
        $timestamp = time();
        $expiresAfter = 200;
        $expiresAt = $timestamp + $expiresAfter;

        $item = new CacheItem('foo', true);
        $item->expiresAfter($expiresAfter);

        $this->assertEquals($expiresAt, $item->getExpiresAt());
        $this->assertEquals($expiresAfter, $item->getExpiresAfter());

        $item->expiresAfter(null);

        $this->assertNull($item->getExpiresAt());
        $this->assertNull($item->getExpiresAfter());
    }
}
