<?php

namespace Hyqo\Cache\Adapter;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Contract\ItemInterface;
use Hyqo\Cache\Contract\PoolInterface;

class RuntimeAdapter implements PoolInterface
{
    protected array $storage = [];

    public function __construct(
        protected ?int $ttl = null,
    ) {
    }

    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->storage)) {
            [$expiresAt] = $this->storage[$id];

            if (null === $expiresAt || $expiresAt > time()) {
                return true;
            }
        }

        return false;
    }

    public function get(string $id, ?callable $handle = null): ItemInterface
    {
        if ($this->has($id)) {
            [$expiresAt, $value] = $this->storage[$id];

            return (new CacheItem($id, true))->set($value)->expiresAt($expiresAt);
        }

        $item = new CacheItem($id, false);

        if (null !== $handle) {
            $item->set($handle($item));
        }

        $this->save($item);

        return $item;
    }

    public function delete(string $id): bool
    {
        unset($this->storage[$id]);

        return true;
    }

    public function save(ItemInterface $item): bool
    {
        if (null !== $this->ttl && null === $item->getExpiresAt()) {
            $item->expiresAfter($this->ttl);
        }

        $this->storage[$item->getKey()] = [
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
