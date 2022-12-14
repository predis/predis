<?php

namespace Predis\Command\Traits\With;

trait WithDist
{
    use BaseWith;

    private function getKeyword(): string
    {
        return 'WITHDIST';
    }

    private function getArgumentPositionOffset(): int
    {
        return static::$withDistArgumentPositionOffset;
    }
}
