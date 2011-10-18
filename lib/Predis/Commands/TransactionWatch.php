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

class TransactionWatch extends Command
{
    public function getId()
    {
        return 'WATCH';
    }

    protected function filterArguments(Array $arguments)
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }

    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed()
    {
        return false;
    }

    public function parseResponse($data)
    {
        return (bool) $data;
    }
}
