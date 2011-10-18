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

/**
 * Class that defines a few helpers method for prefixing keys.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PrefixHelpers
{
    /**
     * Applies the specified prefix to all the arguments.
     *
     * @param array $arguments Array of arguments.
     * @param string $prefix The prefix string.
     * @return array
     */
    public static function multipleKeys(Array $arguments, $prefix)
    {
        foreach ($arguments as &$key) {
            $key = "$prefix$key";
        }

        return $arguments;
    }

    /**
     * Applies the specified prefix to all the arguments but the last one.
     *
     * @param array $arguments Array of arguments.
     * @param string $prefix The prefix string.
     * @return array
     */
    public static function skipLastArgument(Array $arguments, $prefix)
    {
        $length = count($arguments);
        for ($i = 0; $i < $length - 1; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        return $arguments;
    }
}
