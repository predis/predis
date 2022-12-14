<?php

namespace Predis\Command\Traits\With;

class WithHash
{
    use BaseWith;

    private function getKeyword(): string
    {
        return 'WITHHASH';
    }

    private function getArgumentPositionOffset(): int
    {
        return static::$withHashArgumentPositionOffset;
    }
}
