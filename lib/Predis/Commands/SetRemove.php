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

use Predis\Helpers;

class SetRemove extends Command
{
    public function getId()
    {
        return 'SREM';
    }

    protected function filterArguments(Array $arguments)
    {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data)
    {
        return (bool) $data;
    }
}
