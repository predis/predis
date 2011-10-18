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

class ServerSlaveOf extends Command
{
    public function getId()
    {
        return 'SLAVEOF';
    }

    protected function filterArguments(Array $arguments)
    {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }

        return $arguments;
    }

    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        /* NOOP */
    }

    protected function canBeHashed()
    {
        return false;
    }
}
