<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait MinMaxModifier
{
    /**
     * @var array{string: string}
     */
    private $modifierEnum = [
        'min' => 'MIN',
        'max' => 'MAX',
    ];

    public function resolveModifier(int $offset, array &$arguments): void
    {
        if ($offset >= count($arguments)) {
            $arguments[$offset] = $this->modifierEnum['min'];
            return;
        }

        if (!is_string($arguments[$offset]) || !array_key_exists($arguments[$offset], $this->modifierEnum)) {
            throw new UnexpectedValueException('Wrong type of modifier given');
        }

        $arguments[$offset] = $this->modifierEnum[$arguments[$offset]];
    }
}
