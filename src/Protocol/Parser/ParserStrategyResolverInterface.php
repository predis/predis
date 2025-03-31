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

namespace Predis\Protocol\Parser;

use Predis\Protocol\Parser\Strategy\ParserStrategyInterface;

interface ParserStrategyResolverInterface
{
    /**
     * Resolves parser strategy according given protocol version.
     *
     * @param  int                     $protocolVersion
     * @return ParserStrategyInterface
     */
    public function resolve(int $protocolVersion): ParserStrategyInterface;
}
