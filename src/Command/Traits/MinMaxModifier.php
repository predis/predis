<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
