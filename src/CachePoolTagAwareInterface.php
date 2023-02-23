<?php

namespace Hyqo\Cache;

interface CachePoolTagAwareInterface extends CachePoolInterface
{
    public function flushTag(string $tag): bool;
}
