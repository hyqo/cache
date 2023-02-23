<?php

namespace Hyqo\Cache\Adapter;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\CachePoolInterface;

class RuntimeAdapter implements CachePoolInterface
{
    protected array $storage = [];

    public function hasItem(string $key): bool
    {
        if (array_key_exists($key, $this->storage)) {
            [$expiresAt] = $this->storage[$key];

            if (null === $expiresAt || $expiresAt > time()) {
                return true;
            }
        }

        return false;
    }

    public function getItem(string $key, ?callable $handle = null): CacheItem
    {
        if ($this->hasItem($key)) {
            [$expiresAt, $value] = $this->storage[$key];

            return (new CacheItem($key, true, $value))->expiresAt($expiresAt);
        }

        $item = new CacheItem($key, false);

        null !== $handle && $handle($item);

        $this->save($item);

        return $item;
    }

    public function delete(string $key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    public function save(CacheItem $item): bool
    {
        $this->storage[$item->key] = [
            $item->getExpiresAt(),
            $item->get(),
        ];

        return true;
    }

    public function flush(): bool
    {
        $this->storage = [];

        return true;
    }
}
