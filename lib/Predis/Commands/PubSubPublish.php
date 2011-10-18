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

class PubSubPublish extends Command
{
    public function getId()
    {
        return 'PUBLISH';
    }

    protected function canBeHashed()
    {
        return false;
    }
}
