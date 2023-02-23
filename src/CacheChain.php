<?php

namespace Hyqo\Cache;

use Hyqo\Cache\Exception\CacheException;

class CacheChain implements CachePoolInterface
{
    /**
     * @var CachePoolInterface[]
     */
    protected array $pools;

    /**
     * @param CachePoolInterface[] $pools
     */
    public function __construct(array $pools)
    {
        if (!count($pools)) {
            throw new CacheException('At least one cache pool must be provide');
        }

        $this->pools = $pools;
    }

    public function hasItem(string $key): bool
    {
        foreach ($this->pools as $pool) {
            if ($pool->hasItem($key)) {
                return true;
            }
        }

        return false;
    }

    public function getItem(string $key, ?callable $handle = null): CacheItem
    {
        foreach ($this->pools as $i => $pool) {
            if ($pool->hasItem($key)) {
                $item = $pool->getItem($key);

                while (--$i >= 0) {
                    $this->pools[$i]->save(clone $item);
                }

                return $item;
            }
        }

        $item = new CacheItem($key, false);

        null !== $handle && $handle($item);

        foreach ($this->pools as $pool) {
            $pool->save($item);
        }

        return $item;
    }

    public function delete(string $key): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->delete($key) || $ok;
        }

        return $ok;
    }

    public function flush(): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->flush() || $ok;
        }

        return $ok;
    }

    public function save(CacheItem $item): bool
    {
        foreach ($this->pools as $pool) {
            $pool->save($item);
        }

        return true;
    }
}
