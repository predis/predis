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

namespace Predis\Command\Traits\Expire;

trait ExpireOptions
{
    private static $argumentEnum = [
        'nx' => 'NX',
        'xx' => 'XX',
        'gt' => 'GT',
        'lt' => 'LT',
    ];

    public function setArguments(array $arguments)
    {
        $value = array_pop($arguments);

        if (null === $value) {
            parent::setArguments($arguments);

            return;
        }

        if (in_array(strtoupper($value), self::$argumentEnum, true)) {
            $arguments[] = self::$argumentEnum[strtolower($value)];
        } else {
            $arguments[] = $value;
        }

        parent::setArguments($arguments);
    }
}
