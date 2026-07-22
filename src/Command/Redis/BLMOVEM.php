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

namespace Predis\Command\Redis;

/**
 * @see https://redis.io/commands/blmovem/
 *
 * Blocking variant of LMOVEM. When the request cannot be satisfied
 * immediately, blocks the connection until it can be or until timeout
 * (seconds, 0 blocks indefinitely) elapses, returning null if nothing was
 * moved. The timeout argument is positioned after the directions and before
 * the optional quantifier block.
 */
class BLMOVEM extends LMOVEM
{
    public function getId()
    {
        return 'BLMOVEM';
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuantifierOffset(): int
    {
        return 5;
    }
}
