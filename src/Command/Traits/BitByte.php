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

trait BitByte
{
    private static $argumentEnum = [
        'bit' => 'BIT',
        'byte' => 'BYTE',
    ];

    public function setArguments(array $arguments)
    {
        $value = array_pop($arguments);

        if (null === $value) {
            parent::setArguments($arguments);

            return;
        }

        if (in_array(strtoupper($value), self::$argumentEnum, true)) {
            $arguments[] = self::$argumentEnum[$value];
        } else {
            $arguments[] = $value;
        }

        parent::setArguments($arguments);
    }
}
