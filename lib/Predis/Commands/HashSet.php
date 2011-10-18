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

class HashSet extends Command {
    public function getId()
    {
        return 'HSET';
    }

    public function parseResponse($data)
    {
        return (bool) $data;
    }
}
