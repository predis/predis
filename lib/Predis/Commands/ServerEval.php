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

class ServerEval extends Command
{
    public function getId()
    {
        return 'EVAL';
    }

    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        $arguments = $this->getArguments();

        for ($i = 2; $i < $arguments[1] + 2; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        return $arguments;
    }

    protected function canBeHashed()
    {
        return false;
    }
}
