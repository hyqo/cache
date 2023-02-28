<?php

namespace Hyqo\Cache;

use Hyqo\Cache\Contract\TagAwarePoolInterface;

class CacheChainTagAware extends CacheChain implements TagAwarePoolInterface
{
    /**
     * @var TagAwarePoolInterface[]
     */
    protected array $pools;

    public function flushTag(array $tagIds): bool
    {
        $ok = false;

        foreach ($this->pools as $pool) {
            $ok = $pool->flushTag($tagIds) || $ok;
        }

        return $ok;
    }
}
