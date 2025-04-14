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

namespace Predis\Protocol\Parser\Strategy;

interface ParserStrategyInterface
{
    /**
     * Parse given line of RESP protocol string.
     *
     * @param  string $data
     * @return mixed
     */
    public function parseData(string $data);
}
