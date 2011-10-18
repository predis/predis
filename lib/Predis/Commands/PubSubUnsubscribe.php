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

class PubSubUnsubscribe extends Command
{
    public function getId()
    {
        return 'UNSUBSCRIBE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed()
    {
        return false;
    }
}
