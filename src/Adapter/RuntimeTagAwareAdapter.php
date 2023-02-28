<?php

namespace Hyqo\Cache\Adapter;

use Hyqo\Cache\Contract\ItemInterface;
use Hyqo\Cache\Contract\TagAwarePoolInterface;
use Hyqo\Cache\Trait\TagAwarePoolTrait;

class RuntimeTagAwareAdapter extends RuntimeAdapter implements TagAwarePoolInterface
{
    use TagAwarePoolTrait;

    protected array $tagStorage = [];

    public function flush(): bool
    {
        $this->tagStorage = [];

        return parent::flush();
    }

    public function delete(string $id): bool
    {
        $tags = $this->storage[$id][1] ?? [];

        foreach ($tags as $tag) {
            $this->removeItemFromTag($id, $tag);
        }

        return parent::delete($id);
    }

    public function save(ItemInterface $item): bool
    {
        $newTags = $item->getTags();
        $oldTags = $this->storage[$item->getKey()][1] ?? [];

        [$addedTags, $removedTags] = $this->diffTags($newTags, $oldTags);

        $this->storage[$item->getKey()] = [
            $item->getExpiresAt(),
            $item->getTags(),
            $item->get(),
        ];

        foreach ($addedTags as $tag) {
            $this->tagStorage[$tag] ??= [];
            $this->tagStorage[$tag][] = $item->getKey();
        }

        foreach ($removedTags as $tag) {
            $this->removeItemFromTag($item->getKey(), $tag);
        }

        return true;
    }

    public function flushTag(array $tagIds): bool
    {
        foreach ($tagIds as $tag) {
            if (array_key_exists($tag, $this->tagStorage)) {
                foreach ($this->tagStorage[$tag] as $key) {
                    $this->delete($key);
                }
            }
        }

        return true;
    }

    protected function removeItemFromTag(string $id, string $tag): void
    {
        if (!array_key_exists($tag, $this->tagStorage)) {
            return;
        }

        $this->tagStorage[$tag] = array_filter(
            $this->tagStorage[$tag],
            static fn(string $storedId) => $storedId !== $id
        );

        if(!count($this->tagStorage[$tag])){
            unset($this->tagStorage[$tag]);
        }
    }
}
