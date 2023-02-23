<?php

namespace Hyqo\Cache\Test\Fixtures;

use Hyqo\Cache\Adapter\RuntimeAdapter;

class ImmodestRuntimeAdapter extends RuntimeAdapter
{
    public function &storage(): array
    {
        return $this->storage;
    }
}
