<?php

namespace Predis\Command\Traits\With;

use Predis\Command\Command;

/**
 * @mixin Command
 */
trait WithCoord
{
    use BaseWith;

    private function getKeyword(): string
    {
        return 'WITHCOORD';
    }

    private function getArgumentPositionOffset(): int
    {
        return static::$withCoordArgumentPositionOffset;
    }
}
