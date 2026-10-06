<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Container;

/**
 * @method array get(string $key)                 Returns the flags currently set on the key.
 * @method int   set(string $key, string $flag)   Returns 1 if the flag was added, 0 if it was already set.
 * @method int   clear(string $key, string $flag) Returns 1 if the flag was removed, 0 if it was not set.
 */
class BLESS extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'BLESS';
    }

    /**
     * Iterates over the keys carrying the given flag.
     *
     * Mirrors the plain SCAN command signature: options are passed as an
     * array, e.g. ['COUNT' => 10].
     *
     * @param  string|int  $cursor  Cursor returned by the previous call, 0 to start a new iteration.
     * @param  string      $flag    Flag to scan for.
     * @param  array|null  $options Scan options, supports COUNT.
     * @return array|mixed [next cursor, list of keys]; an error response when the client runs with exceptions disabled
     */
    public function scan($cursor, string $flag, ?array $options = null)
    {
        return $this->__call('SCAN', func_get_args());
    }
}
