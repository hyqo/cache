<?php

namespace Hyqo\Cache\Adapter;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\CachePoolTagAwareInterface;

class RuntimeTagAwareAdapter extends RuntimeAdapter implements CachePoolTagAwareInterface
{
    protected array $tags = [];

    public function flush(): bool
    {
        $this->tags = [];

        return parent::flush();
    }


    public function delete(string $key): bool
    {
        $tags = $this->storage[$key][1] ?? [];

        foreach ($tags as $tag) {
            if (!array_key_exists($tag, $this->tags)) {
                continue;
            }

            $itemTags = &$this->tags[$tag];

            if (false !== $index = array_search($key, $itemTags, true)) {
                unset($itemTags[$index]);

                if (!count($itemTags)) {
                    unset($this->tags[$tag]);
                } else {
                    $this->tags[$tag] = array_values($itemTags);
                }
            }
        }

        return parent::delete($key);
    }

    public function save(CacheItem $item): bool
    {
        $this->storage[$item->key] = [
            $item->getExpiresAt(),
            $item->getTags(),
            $item->get(),
        ];

        foreach ($item->getTags() as $tag) {
            $this->tags[$tag] = array_unique([...($this->tags[$tag] ?? []), $item->key]);
        }

        return true;
    }

    public function flushTag(string $tag): bool
    {
        if (array_key_exists($tag, $this->tags)) {
            foreach ($this->tags[$tag] as $key) {
                $this->delete($key);
            }
        }

        return true;
    }
}
