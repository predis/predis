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
 * @see https://redis.io/commands/zmovemember/
 *
 * Atomically moves the given members from one sorted set to another,
 * preserving their scores. NX moves only members absent from the destination,
 * XX only members already present in it.
 */
class ZMOVEMEMBER extends ZMOVE
{
    public function getId()
    {
        return 'ZMOVEMEMBER';
    }

    public function setArguments(array $arguments)
    {
        $processed = array_slice($arguments, 0, 2);

        if (isset($arguments[3])) {
            $processed[] = strtoupper($arguments[3]);
        }

        if (isset($arguments[2])) {
            $members = (array) $arguments[2];
            array_push($processed, 'MEMBERS', count($members), ...array_values($members));
        }

        $this->setRawArguments($processed);
    }
}
