<?php

namespace Hyqo\Cache\Test\Fixtures;

use Hyqo\Cache\Adapter\RuntimeTagAwareAdapter;

class ImmodestRuntimeTagAwareAdapter extends RuntimeTagAwareAdapter
{
    public function &storage(): array
    {
        return $this->storage;
    }

    public function &tags(): array
    {
        return $this->tags;
    }
}
