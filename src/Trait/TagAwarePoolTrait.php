<?php

namespace Hyqo\Cache\Trait;

use JetBrains\PhpStorm\ArrayShape;

trait TagAwarePoolTrait
{
    #[ArrayShape(['array', 'array'])]
    protected function diffTags(array $newTags, array $oldTags): array
    {
        return [
            array_diff($newTags, $oldTags),
            array_diff($oldTags, $newTags),
        ];
    }
}
