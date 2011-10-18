<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

class PrefixHelpers
{
    public static function multipleKeys(Array $arguments, $prefix)
    {
        foreach ($arguments as &$key) {
            $key = "$prefix$key";
        }

        return $arguments;
    }

    public static function skipLastArgument(Array $arguments, $prefix)
    {
        $length = count($arguments);
        for ($i = 0; $i < $length - 1; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        return $arguments;
    }
}
