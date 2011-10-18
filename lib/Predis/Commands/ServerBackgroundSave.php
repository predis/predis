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

class ServerBackgroundSave extends Command
{
    public function getId()
    {
        return 'BGSAVE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        /* NOOP */
    }

    protected function canBeHashed()
    {
        return false;
    }

    public function parseResponse($data)
    {
        if ($data == 'Background saving started') {
            return true;
        }

        return $data;
    }
}
