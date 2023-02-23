<?php

namespace Hyqo\Cache;

class CacheChainTagAware extends CacheChain implements CachePoolTagAwareInterface
{
    /**
     * @var CachePoolTagAwareInterface[]
     */
    protected array $pools;

    /**
     * @param CachePoolTagAwareInterface[] $pools
     */
    public function __construct(array $pools)
    {
        parent::__construct($pools);
    }

    public function flushTag(string $tag): bool
    {
        $ok = false;

        foreach ($this->pools as $pool) {
            $ok = $pool->flushTag($tag) || $ok;
        }

        return $ok;
    }
}
