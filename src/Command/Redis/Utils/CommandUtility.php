<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Utils;

use UnexpectedValueException;

class CommandUtility
{
    /**
     * Converts RESP2 array into RESP3 dictionary.
     *
     * @param  array         $array
     * @param  callable|null $callback  Callback that applies to each key, value (except arrays) before convert them into key => value
     * @param  bool          $recursive
     * @return array
     */
    public static function arrayToDictionary(array $array, ?callable $callback = null, bool $recursive = true): array
    {
        if (count($array) % 2 !== 0) {
            throw new UnexpectedValueException('Array must have an even number of arguments');
        }

        $dict = [];

        for ($i = 0; $i < count($array); $i += 2) {
            if (is_array($array[$i + 1])) {
                if ($recursive) {
                    $dict[$array[$i]] = self::arrayToDictionary($array[$i + 1], $callback, $recursive);
                } else {
                    $dict[$array[$i]] = $array[$i + 1];
                }
            } else {
                if ($callback) {
                    [$key, $value] = $callback($array[$i], $array[$i + 1]);
                } else {
                    $key = $array[$i];
                    $value = $array[$i + 1];
                }

                $dict[$key] = $value;
            }
        }

        return $dict;
    }
}
