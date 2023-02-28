<?php

namespace Hyqo\Cache;

use Hyqo\Cache\Contract\ItemInterface;
use Hyqo\Cache\Contract\PoolInterface;
use Hyqo\Cache\Exception\CacheException;

class CacheChain implements PoolInterface
{
    /**
     * @var PoolInterface[]
     */
    protected array $pools;

    /**
     * @param PoolInterface[] $pools
     */
    public function __construct(array $pools)
    {
        if (!count($pools)) {
            throw new CacheException('At least one cache pool must be provide');
        }

        $this->pools = $pools;
    }

    public function has(string $id): bool
    {
        foreach ($this->pools as $pool) {
            if ($pool->has($id)) {
                return true;
            }
        }

        return false;
    }

    public function get(string $id, ?callable $handle = null): CacheItem
    {
        foreach ($this->pools as $i => $pool) {
            $item = $pool->get($id);

            if ($item->isHit()) {
                while (--$i >= 0) {
                    $this->pools[$i]->save(clone $item);
                }

                return $item;
            }
        }

        $item = new CacheItem($id, false);

        if (null !== $handle) {
            $item->set($handle($item));
        }

        foreach ($this->pools as $pool) {
            $pool->save(clone $item);
        }

        return $item;
    }

    public function delete(string $id): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->delete($id) || $ok;
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

    public function save(ItemInterface $item): bool
    {
        $ok = true;

        foreach ($this->pools as $pool) {
            $ok = $pool->save(clone $item) && $ok;
        }

        return true;
    }
}
