<?php

namespace Hyqo\Cache;

interface CachePoolInterface
{
    public function hasItem(string $key): bool;

    public function getItem(string $key, ?callable $handle = null): CacheItem;

    public function delete(string $key): bool;

    public function save(CacheItem $item): bool;

    public function flush(): bool;
}
